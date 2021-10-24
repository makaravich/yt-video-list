<?php


class ImcaYoutubeFeedCore
{
    /**
     * @var array
     */
    protected array $featuredPlaylists = [
        'PLS1zXxHhA212fso2CBuBmNNMycOK_E9P2',
        'PLS1zXxHhA211AkfqRx91Kqa9pHnraiKiq',
        'PLS1zXxHhA2119uhrIZUlZjqT-NuIa79xB',
        'PLS1zXxHhA213l__BRDoNcA2oiZLx5X8Gp',
        'PLS1zXxHhA212d3vF1F1Lm81j0-ED2N4IB',
        'PLS1zXxHhA210vpZ3bdX2gtzGgp4Y90nI6',
    ];


    private function get_settings( $name )
    {
        $val = get_option( IMCA_YTF_OPTION_NAME );
        $val = $val ? $val[ $name ] : null;

        return $val;
    }

    /**
     * Returns API key for YouTube API
     * @return string
     */
    private function get_api_key(): string
    {
        //return 'AIzaSyD4TJ74OaL8fOzqCyxALBvHSWRsbAL2xSI';
        return $this->get_settings( 'imca_ytf_api_key' );
    }

    /**
     * Returns the playlist ID for YouTube API
     * @return string
     */
    private function get_playlist_id(): string
    {
        //return 'UUMsWGSi9umtYQoUI3kwJJmA';
        return $this->get_settings( 'imca_ytf_playlist_id' );
    }

    /**
     * Returns Youtube API URL
     * @return string
     */
    private function get_api_url( $api_name ): string
    {
        $api_url = $this->get_settings( 'imca_ytf_api_url' );
        if ( substr( $api_url, -1 ) !== '/' ) $api_url .= '/';
        return $api_url . $api_name;

        //return 'https://youtube.googleapis.com/youtube/v3/videos';
        //return $this->get_settings('imca_ytf_api_url');
    }

    /**
     * Gets the playlist option name
     * @return string
     */
    private function get_option_name(): string
    {
        return 'imca_yt_feed';
    }


    /**
     * Returns count of videos on a page
     * @return int
     */
    private function get_items_on_page_count()
    {
        //return 30;
        return $this->get_settings( 'imca_ytf_items_on_page' );
    }

    /**
     * Returns period for updating of the playlist (in seconds)
     * @return int
     */
    private function get_update_period(): int
    {
        //return 24 * 60 * 60;
        //return 2;
        return $this->get_settings( 'imca_ytf_update_period' );
    }

    public function to_time_ago( $time )
    {

        // Calculate difference between current
        // time and given timestamp in seconds
        $time = strtotime( $time );

        $diff = time() - $time;

        if ( $diff < 1 ) {
            return 'less than 1 second ago';
        }

        $time_rules = array(
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60      => 'month',
            24 * 60 * 60           => 'day',
            60 * 60                => 'hour',
            60                     => 'minute',
            1                      => 'second',
        );

        foreach ( $time_rules as $secs => $str ) {

            $div = $diff / $secs;

            if ( $div >= 1 ) {

                $t = round( $div );

                return $t . ' ' . $str .
                       ( $t > 1 ? 's' : '' ) . ' ago';
            }
        }
    }


    public function call_youtube_api( $api, $params = false )
    {
        $url = $this->get_api_url( $api );

        $params[ 'key' ] = $this->get_api_key();

        $url = $url . '?' . http_build_query( $params );

        //$this->write_Log( '$url: ' . $url );

        $ch = curl_init( $url );

        # Return response instead of printing.
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        # Send request.
        $response = curl_exec( $ch );

        // $this->write_Log( $response );

        curl_close( $ch );

        return json_decode( $response );
    }

    /**
     * Gets video statistic from Youtube API
     * @param $video_id
     * @return mixed
     */
    public function get_video_stat( $video_id )
    {
        $params = [
            'part' => 'statistics,recordingDetails,contentDetails',
            'id'   => $video_id,
        ];
        $stat   = $this->call_youtube_api( 'videos', $params );

        /*        $res[] = $stat->items[0]->contentDetails;
                $res[] = $stat->items[0]->statistics;
                $res[] = $stat->items[0]->recordingDetails;*/

        return $stat->items[ 0 ];
    }

    /**
     * Returns one page of data for YouTube videos
     * @param $page_token
     * @return mixed
     */
    private function get_single_yt_page_data( $page_token )
    {
        $playlist_id = $this->get_playlist_id();
        //$api_url     = $this->get_api_url( 'playlistItems' );

        $request_params = [
            'part'       => 'snippet',
            'playlistId' => $playlist_id,
            'maxResults' => 50,
        ];

        if ( $page_token !== 1 ) $request_params[ 'pageToken' ] = $page_token;

        return $this->call_youtube_api( 'playlistItems', $request_params );
    }

    /**
     * Builds the playlist of Youtube items and saves it in options
     */
    private function build_playlist()
    {
        $opt_videos = [];
        $featured   = [];

        $next_page = 1;

        while ( $next_page ) :
            $videos_data = $this->get_single_yt_page_data( $next_page );
            $videos      = $videos_data->items;

            $next_page = $videos_data->nextPageToken;

            foreach ( $videos as $video ) {
                $video->stat  = $this->get_video_stat( $video->snippet->resourceId->videoId );
                $opt_videos[] = $video;
            }
        endwhile;

        foreach ( $this->featuredPlaylists as $playlist_id ) {
            $request_params = [
                'part'       => 'snippet',
                'playlistId' => $playlist_id,
                'maxResults' => $this->get_settings( 'videos_per_line' ),
            ];

            $f_videos = $this->call_youtube_api( 'playlistItems', $request_params );

            $playlist = $this->call_youtube_api( 'playlists', [ 'part' => 'snippet', 'id' => $playlist_id ] );

            foreach ( $f_videos->items as $f_video ) {
                $f_video->stat = $this->get_video_stat( $f_video->snippet->resourceId->videoId );
            }

            $featured[] = [
                'videos' => $f_videos,
                'meta'   => $playlist,
            ];

            //$this->write_log( $featured );

        }

        $opt = [
            'videos'      => $opt_videos,
            'featured'    => $featured,
            'last_update' => time(),
        ];

        update_option( $this->get_option_name(), $opt );
    }

    /**
     * Periodically updates the playlist
     */
    private function update_playlist()
    {
        $opt = get_option( $this->get_option_name() );

        $update_period = $this->get_update_period();

        //echo time() . ', ' . $update_period . ', ' . (time() - $opt['last_update']) . ', <pre>' . print_r($opt, true) . '</pre>';

        if ( !$opt[ 'last_update' ] ||
             ( time() - $opt[ 'last_update' ] ) > $update_period ) {
            $this->build_playlist();
        }
    }

    /**
     * Returns all saved video items data
     * @return array
     */
    private function get_all_video_items( $list_type = 'videos' ): array
    {
        $this->update_playlist();
        $opt = get_option( $this->get_option_name() );

        //$this->write_log( $opt );

        return $opt[ $list_type ];
    }

    /**
     * Returns video items data for particular page
     * @param int $page_no
     * @param int $items_per_page
     *
     * @return stdClass;
     */
    public function get_video_page_data( $page_no = 0, $items_per_page = 0 ): stdClass
    {
        if ( $items_per_page === 0 ) $items_per_page = $this->get_items_on_page_count();

        $all_video_items = $this->get_all_video_items();
        $list            = [];

        for ( $i = $page_no * $items_per_page; $i < ( ( $page_no + 1 ) * $items_per_page ); $i++ ) {
            $list[] = $all_video_items[ $i ];
        }

        $res = new stdClass();

        $res->list    = $list;
        $res->pageNum = $page_no;

        if ( (int)$page_no >= 0 ) $res->prevPageNum = $page_no + 1;
        else $res->prevPageNum = '';

        $nextElemIdx = ( ( $page_no + 1 ) * $items_per_page );
        if ( isset( $all_video_items[ $nextElemIdx ] ) ) $res->nextPageNum = $page_no + 3;
        else $res->nextPageNum = $this->get_last_page_num() + 2;

        return $res;
    }

    private function render_featured_playlists(): string
    {
        $featured = $this->get_all_video_items( 'featured' );
        //$this->write_log( $featured );
        ob_start();
        foreach ( $featured as $playlist ) {

            $title = $playlist[ 'meta' ]->items[ 0 ]->snippet->title;

            $this->render_list_header( $title, $playlist[ 'meta' ]->items[ 0 ]->id );
            foreach ( $playlist[ 'videos' ]->items as $video ) {
                //$video->stat = $this->get_video_stat( $video->snippet->resourceId->videoId );
                echo $this->render_single_video( $video );
                // echo '***';
            }
        }
        ?>
        <div class="nav-buttons">
            <?php echo $this->render_nav_buttons( $this->get_last_page_num(), 0 ); ?>
        </div>
        <?php
        echo $this->render_js_videos();
        return ob_get_clean();

        //return '<pre>' . print_r( $res, true ) . '</pre>';
    }

    /**
     * Returns HTML code of videos. Used also for the shortcode (controller)
     *
     * @param array $args
     * @return false|string
     */
    public function youtube_feed( $args = [] )
    {
        if ( empty( $args ) ) {
            $args = [
                'page'            => '0',
                'videos_per_line' => '5',
                'max_results'     => $this->get_items_on_page_count(),
            ];
        }

        $paged = $_REQUEST[ 'page_no' ] ?? 1;

        if ( $args[ 'page' ] !== 0 && $paged ) $args[ 'page' ] = (int)$paged - 1;

        //$this->write_log( $args );

        //$this->write_log( "Current page: {$args['page']}" );
        if ( $args[ 'page' ] == $this->get_last_page_num() ) {

            return $this->render_featured_playlists();
        }
        else {
            $list = $this->get_video_page_data( $args[ 'page' ], $args[ 'max_results' ] );
            return $this->render_video_list( $list );
        }
    }

    /**
     * Returns last page number for all videos, excluding featured videos
     * @return int
     */
    private function get_last_page_num(): int
    {
        $videos         = $this->get_all_video_items();
        $items_per_page = $this->get_items_on_page_count();

        $count = ceil( count( $videos ) / $items_per_page );
        //$this->write_log( $count );

        return (int)$count;
    }

    /**
     * Renders or returns header for a playlist
     *
     * @param string $title
     * @param string $playlist_id
     * @param bool $return
     * @return void
     */
    private function render_list_header( string $title, string $playlist_id = '', bool $return = false )
    {
        ob_start();
        ?>
        <div class="playlist-title">
            <div class="pl-left">
                <h2 class="playlist-title"><?php echo $title; ?></h2>
            </div>
            <div class="pl-right">
                <?php if ( $playlist_id ): ?>
                    <h2 class="play-all"><a
                                href="https://www.youtube.com/playlist?list=<?php echo $playlist_id ?>"
                                target="_blank" rel="noopener noreferrer">Play All <span class="play-btn">►</span></a>
                    </h2>
                <?php endif; ?>
            </div>
        </div>
        <?php

        if ( $return ) {
            ob_get_clean();
        }
        else {
            echo ob_get_clean();
        }
    }

    /**
     * Get HTML code of the playlist (view)
     *
     * @param $videos
     * @return false|string
     */
    private function render_video_list( $videos )
    {
        $items = $videos->list;

        $prev_page_num = $videos->prevPageNum;
        $next_page_num = $videos->nextPageNum;

        ob_start();
        $this->render_list_header( $this->get_settings( 'main_pl_title' ), $this->get_playlist_id() );
        ?>
        <div class="imca-videos-list">
            <div class="videos">
                <?php
                foreach ( $items as $key => $item ) {
                    echo $this->render_single_video( $item );
                }
                ?>
            </div>
            <div class="nav-buttons">
                <?php echo $this->render_nav_buttons( $prev_page_num - 1, $next_page_num - 1 ); ?>
            </div>
            <!--<pre> <? /*= print_r($videos, true); */ ?> </pre>-->
        </div>
        <?php

        echo $this->render_js_videos();

        return ob_get_clean();
    }

    /**
     * Builds a single video block
     * @param $video
     * @return string
     */
    private function render_single_video( $video )
    {
        //echo '<pre>' . print_r($video, true) . '</pre>';
        //$this->write_log( $video );
        ob_start();
        if ( $video ) :
            ?>
            <div class="imca-vl-single-video" data-video_id="<?php echo $video->snippet->resourceId->videoId; ?>">
                <!--<iframe width="360" height="240"
                        src="https://www.youtube.com/embed/<?/*= $item->snippet->resourceId->videoId */ ?>" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>-->
                <div class="img-wrapper">
                    <img class="single-video-picture" src="<?= $video->snippet->thumbnails->medium->url ?>"/>
                    <div class="yt-play-wrapper">
                        <?php echo $this->get_icon( 'youtube', [ 48, 'auto' ], 'youtube-play' ); ?>
                    </div>
                    <div class="duration"><?php echo $this->covtime( $video->stat->contentDetails->duration ) ?></div>
                </div>

                <div class="stat-wrapper">
                    <?php
                    if ( isset( $video->stat->recordingDetails->recordingDate ) ) :
                        echo $this->get_icon( 'clock', [ 13, 13 ] ) ?>
                        <span class="rec-date"><?php echo $this->to_time_ago( $video->stat->recordingDetails->recordingDate ) ?></span>
                    <?php endif; ?>

                    <?php echo $this->get_icon( 'view', [ 13, 13 ] ) ?>
                    <span class="view-count"><?php echo $video->stat->statistics->viewCount ?></span>

                    <?php echo $this->get_icon( 'like', [ 13, 13 ] ) ?>
                    <span class="like-count"><?php echo $video->stat->statistics->likeCount ?></span>
                </div>

                <div class="descript-wrapper">
                    <h4><?= $this->get_words( $video->snippet->title ); ?></h4>
                    <p><?= $this->get_words( $video->snippet->description, 20 ); ?></p>
                </div>

            </div>
        <?php
        endif;
        return ob_get_clean();
    }

    /**
     * @param $youtube_time
     * @return string
     */
    private function covtime( $youtube_time )
    {
        if ( !$youtube_time ) return '';

        $di     = new DateInterval( $youtube_time );
        $string = '';

        if ( $di->h > 0 ) {
            $string .= sprintf( '%02s', $di->h ) . ':';
        }

        return $string . sprintf( '%02s', $di->i ) . ':' . sprintf( '%02s', $di->s );
    }

    /**
     * Get HTML of Previous/Next buttons
     *
     * @param string $prev_token
     * @param string $next_token
     * @return string
     */
    private function render_nav_buttons( $prev_token, $next_token ): string
    {
        global $wp;

        if ( $prev_token >= 0 && $prev_token !== '' ) {
            $prev_link = add_query_arg( 'page_no', $prev_token );
            //$prev_link = get_the_permalink() . sprintf("%'.04d", ((int)$next_token - 1)) . '/';
            $prev_class = 'class="enabled"';
        }
        else {
            $prev_link  = get_the_permalink();
            $prev_class = 'class="disabled" onclick="return false;"';
        }

        if ( $next_token ) {
            $next_link = add_query_arg( 'page_no', $next_token );
            //$next_link = get_the_permalink() . sprintf("%'.04d", ((int)$next_token + 1)) . '/';
            $next_class = 'class="enabled"';
        }
        else {
            $next_link  = get_the_permalink();
            $next_class = 'class="disabled" onclick="return false;"';
        }

        $res = '';


        $res .= '<a ' . $prev_class . ' href="' . $prev_link . '"> << Previous</a>';
        $res .= '<a ' . $next_class . ' href="' . $next_link . '">Next >> </a>';

        return $res;
    }

    private function render_js_videos()
    {
        ob_start();
        ?>
        <script type="text/javascript">
            let imca_video_list = document.querySelectorAll('.imca-vl-single-video');

            if (imca_video_list) {
                imca_video_list.forEach(function (item, i, imca_video_list) {
                    item.addEventListener('click', function () {
                        imca_open_video(item.dataset.video_id)
                    });
                });
            }

            function imca_open_video(video_id) {
                console.log(video_id);
                let imcaCinema = document.createElement('div');
                imcaCinema.className = "imca-cinema";
                imcaCinema.innerHTML = '<div class="cinema-close">X</div><div class="imca-cinema-video-wrapper"><iframe width="100%" height="800px" src="https://www.youtube.com/embed/' + video_id +
                    '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';

                imcaCinema.addEventListener('click', function () {
                    this.remove();
                });
                document.body.append(imcaCinema);
            }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Returns $count most left words of the $sentence
     *
     * @param string $sentence
     * @param int $count
     * @return string
     */
    public function get_words( string $sentence, $count = 10 ): string
    {
        if ( !$sentence ) return '';

        $trimmed = implode( ' ', array_slice( explode( ' ', $sentence ), 0, $count ) );

        if ( $trimmed != $sentence ) {
            return $trimmed . '...';
        }
        else {
            return $sentence;
        }
    }

    /**
     * Returns <img> tag of custom icons. Icon names see in $icons_collection
     *
     * @param $name
     * @param array $size
     * @param string $class
     * @return string
     */
    public function get_icon( $name, $size = array( 16, 'auto' ), $class = '' ): string
    {
        if ( !$size ) $size = array( 16, 'auto' );
        if ( $class != '' ) $class = 'class="' . $class . '"';
        else $class = '';

        $icons_collection = array(
            'youtube' => 'PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iNTEyIiB2aWV3Qm94PSIwIC03NyA1MTIuMDAyMTMgNTEyIiB3aWR0aD0iNTEyIj48Zz48cGF0aCBkPSJtNTAxLjQ1MzEyNSA1Ni4wOTM3NWMtNS45MDIzNDQtMjEuOTMzNTk0LTIzLjE5NTMxMy0zOS4yMjI2NTYtNDUuMTI1LTQ1LjEyODkwNi00MC4wNjY0MDYtMTAuOTY0ODQ0LTIwMC4zMzIwMzEtMTAuOTY0ODQ0LTIwMC4zMzIwMzEtMTAuOTY0ODQ0cy0xNjAuMjYxNzE5IDAtMjAwLjMyODEyNSAxMC41NDY4NzVjLTIxLjUwNzgxMyA1LjkwMjM0NC0zOS4yMjI2NTcgMjMuNjE3MTg3LTQ1LjEyNSA0NS41NDY4NzUtMTAuNTQyOTY5IDQwLjA2MjUtMTAuNTQyOTY5IDEyMy4xNDg0MzgtMTAuNTQyOTY5IDEyMy4xNDg0MzhzMCA4My41MDM5MDYgMTAuNTQyOTY5IDEyMy4xNDg0MzdjNS45MDYyNSAyMS45Mjk2ODcgMjMuMTk1MzEyIDM5LjIyMjY1NiA0NS4xMjg5MDYgNDUuMTI4OTA2IDQwLjQ4NDM3NSAxMC45NjQ4NDQgMjAwLjMyODEyNSAxMC45NjQ4NDQgMjAwLjMyODEyNSAxMC45NjQ4NDRzMTYwLjI2MTcxOSAwIDIwMC4zMjgxMjUtMTAuNTQ2ODc1YzIxLjkzMzU5NC01LjkwMjM0NCAzOS4yMjI2NTYtMjMuMTk1MzEyIDQ1LjEyODkwNi00NS4xMjUgMTAuNTQyOTY5LTQwLjA2NjQwNiAxMC41NDI5NjktMTIzLjE0ODQzOCAxMC41NDI5NjktMTIzLjE0ODQzOHMuNDIxODc1LTgzLjUwNzgxMi0xMC41NDY4NzUtMTIzLjU3MDMxMnptMCAwIiBmaWxsPSIjZjAwIiBkYXRhLW9yaWdpbmFsPSIjRjAwIj48L3BhdGg+PHBhdGggZD0ibTIwNC45Njg3NSAyNTYgMTMzLjI2OTUzMS03Ni43NTc4MTItMTMzLjI2OTUzMS03Ni43NTc4MTN6bTAgMCIgZmlsbD0iI2ZmZiIgZGF0YS1vcmlnaW5hbD0iI0ZGRiIgY2xhc3M9ImFjdGl2ZS1wYXRoIiBzdHlsZT0iZmlsbDojRkZGRkZGIiBkYXRhLW9sZF9jb2xvcj0iI2ZmZiI+PC9wYXRoPjwvZz4gPC9zdmc+',
            'clock'   => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTEyIDUxMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPGc+DQoJPGc+DQoJCTxwYXRoIGQ9Ik0zNDcuMjE2LDMwMS4yMTFsLTcxLjM4Ny01My41NFYxMzguNjA5YzAtMTAuOTY2LTguODY0LTE5LjgzLTE5LjgzLTE5LjgzYy0xMC45NjYsMC0xOS44Myw4Ljg2NC0xOS44MywxOS44M3YxMTguOTc4DQoJCQljMCw2LjI0NiwyLjkzNSwxMi4xMzYsNy45MzIsMTUuODY0bDc5LjMxOCw1OS40ODljMy41NjksMi42NzcsNy43MzQsMy45NjYsMTEuODc4LDMuOTY2YzYuMDQ4LDAsMTEuOTk3LTIuNzE3LDE1Ljg4NC03Ljk1Mg0KCQkJQzM1Ny43NjYsMzIwLjIwOCwzNTUuOTgxLDMwNy43NzUsMzQ3LjIxNiwzMDEuMjExeiIvPg0KCTwvZz4NCjwvZz4NCjxnPg0KCTxnPg0KCQk8cGF0aCBkPSJNMjU2LDBDMTE0LjgzMywwLDAsMTE0LjgzMywwLDI1NnMxMTQuODMzLDI1NiwyNTYsMjU2czI1Ni0xMTQuODMzLDI1Ni0yNTZTMzk3LjE2NywwLDI1NiwweiBNMjU2LDQ3Mi4zNDENCgkJCWMtMTE5LjI3NSwwLTIxNi4zNDEtOTcuMDY2LTIxNi4zNDEtMjE2LjM0MVMxMzYuNzI1LDM5LjY1OSwyNTYsMzkuNjU5YzExOS4yOTUsMCwyMTYuMzQxLDk3LjA2NiwyMTYuMzQxLDIxNi4zNDENCgkJCVMzNzUuMjc1LDQ3Mi4zNDEsMjU2LDQ3Mi4zNDF6Ii8+DQoJPC9nPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPC9zdmc+DQo=',
            'view'    => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB2aWV3Qm94PSIwIDAgNTExLjk5OSA1MTEuOTk5IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MTEuOTk5IDUxMS45OTk7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxnPg0KCTxnPg0KCQk8cGF0aCBkPSJNNTA4Ljc0NSwyNDYuMDQxYy00LjU3NC02LjI1Ny0xMTMuNTU3LTE1My4yMDYtMjUyLjc0OC0xNTMuMjA2UzcuODE4LDIzOS43ODQsMy4yNDksMjQ2LjAzNQ0KCQkJYy00LjMzMiw1LjkzNi00LjMzMiwxMy45ODcsMCwxOS45MjNjNC41NjksNi4yNTcsMTEzLjU1NywxNTMuMjA2LDI1Mi43NDgsMTUzLjIwNnMyNDguMTc0LTE0Ni45NSwyNTIuNzQ4LTE1My4yMDENCgkJCUM1MTMuMDgzLDI2MC4wMjgsNTEzLjA4MywyNTEuOTcxLDUwOC43NDUsMjQ2LjA0MXogTTI1NS45OTcsMzg1LjQwNmMtMTAyLjUyOSwwLTE5MS4zMy05Ny41MzMtMjE3LjYxNy0xMjkuNDE4DQoJCQljMjYuMjUzLTMxLjkxMywxMTQuODY4LTEyOS4zOTUsMjE3LjYxNy0xMjkuMzk1YzEwMi41MjQsMCwxOTEuMzE5LDk3LjUxNiwyMTcuNjE3LDEyOS40MTgNCgkJCUM0NDcuMzYxLDI4Ny45MjMsMzU4Ljc0NiwzODUuNDA2LDI1NS45OTcsMzg1LjQwNnoiLz4NCgk8L2c+DQo8L2c+DQo8Zz4NCgk8Zz4NCgkJPHBhdGggZD0iTTI1NS45OTcsMTU0LjcyNWMtNTUuODQyLDAtMTAxLjI3NSw0NS40MzMtMTAxLjI3NSwxMDEuMjc1czQ1LjQzMywxMDEuMjc1LDEwMS4yNzUsMTAxLjI3NQ0KCQkJczEwMS4yNzUtNDUuNDMzLDEwMS4yNzUtMTAxLjI3NVMzMTEuODM5LDE1NC43MjUsMjU1Ljk5NywxNTQuNzI1eiBNMjU1Ljk5NywzMjMuNTE2Yy0zNy4yMywwLTY3LjUxNi0zMC4yODctNjcuNTE2LTY3LjUxNg0KCQkJczMwLjI4Ny02Ny41MTYsNjcuNTE2LTY3LjUxNnM2Ny41MTYsMzAuMjg3LDY3LjUxNiw2Ny41MTZTMjkzLjIyNywzMjMuNTE2LDI1NS45OTcsMzIzLjUxNnoiLz4NCgk8L2c+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8L3N2Zz4NCg==',
            'like'    => 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTEyIDUxMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPGc+DQoJPGc+DQoJCTxwYXRoIGQ9Ik00NjkuNTEsMzE3YzcuMTQtNy45NywxMS40OS0xOC40OSwxMS40OS0zMGMwLTI0LjgxLTIwLjE5LTQ1LTQ1LTQ1aC04Ny4zNGM4LjY1LTI2LjI1LDEyLjM0LTYxLjA4LDEyLjM0LTc2LjAxVjE1MQ0KCQkJYzAtMzMuMDgtMjYuOTItNjAtNjAtNjBoLTE1Yy02Ljg4LDAtMTIuODgsNC42OC0xNC41NSwxMS4zNmwtOC4xNywzMi42OWMtMTEuNDUsNDUuNzgtNDcuOCw5Ni4yOS04NS40MiwxMDUuNDcNCgkJCUMxNzEuMjcsMjIzLjg0LDE1NSwyMTIsMTM2LDIxMkg0NmMtOC4yOCwwLTE1LDYuNzItMTUsMTV2MjcwYzAsOC4yOCw2LjcyLDE1LDE1LDE1aDkwYzE3Ljg5LDAsMzMuMzctMTAuNDksNDAuNjItMjUuNjUNCgkJCWw1MS41NCwxNy4xOGMxNi44NSw1LjYyLDM0LjQxLDguNDcsNTIuMTgsOC40N0g0MDZjMjQuODEsMCw0NS0yMC4xOSw0NS00NWMwLTUuODUtMS4xMi0xMS40NS0zLjE2LTE2LjU4DQoJCQlDNDY2LjkyLDQ0NS4yMSw0ODEsNDI3LjcyLDQ4MSw0MDdjMC0xMS41MS00LjM1LTIyLjAzLTExLjQ5LTMwYzcuMTQtNy45NywxMS40OS0xOC40OSwxMS40OS0zMFM0NzYuNjUsMzI0Ljk3LDQ2OS41MSwzMTd6DQoJCQkgTTE1MSw0NjdjMCw4LjI3LTYuNzMsMTUtMTUsMTVINjFWMjQyaDc1YzguMjcsMCwxNSw2LjczLDE1LDE1VjQ2N3ogTTQwNiwzMzJoMzBjOC4yNywwLDE1LDYuNzMsMTUsMTVjMCw4LjI3LTYuNzMsMTUtMTUsMTVoLTMwDQoJCQljLTguMjgsMC0xNSw2LjcyLTE1LDE1YzAsOC4yOCw2LjcyLDE1LDE1LDE1aDMwYzguMjcsMCwxNSw2LjczLDE1LDE1YzAsOC4yNy02LjczLDE1LTE1LDE1aC0zMGMtOC4yOCwwLTE1LDYuNzItMTUsMTUNCgkJCWMwLDguMjgsNi43MiwxNSwxNSwxNWM4LjI3LDAsMTUsNi43MywxNSwxNWMwLDguMjctNi43MywxNS0xNSwxNUgyODAuMzRjLTE0LjU0LDAtMjguOTEtMi4zMy00Mi43LTYuOTNMMTgxLDQ1Ni4xOVYyNzAuNTgNCgkJCWMyMy41My00LjQ3LDQ2LjU2LTE5LjM3LDY3LjM1LTQzLjc2YzIwLjMtMjMuODIsMzYuNzYtNTUuNCw0NC4wMy04NC40OWw1LjMzLTIxLjMzSDMwMWMxNi41NCwwLDMwLDEzLjQ2LDMwLDMwdjE0Ljk5DQoJCQljMCwyMC4xNC02LjMsNTguNzctMTQuMzYsNzYuMDFIMjg2Yy04LjI4LDAtMTUsNi43Mi0xNSwxNWMwLDguMjgsNi43MiwxNSwxNSwxNWgxNTBjOC4yNywwLDE1LDYuNzMsMTUsMTVjMCw4LjI3LTYuNzMsMTUtMTUsMTUNCgkJCWgtMzBjLTguMjgsMC0xNSw2LjcyLTE1LDE1QzM5MSwzMjUuMjgsMzk3LjcyLDMzMiw0MDYsMzMyeiIvPg0KCTwvZz4NCjwvZz4NCjxnPg0KCTxnPg0KCQk8Y2lyY2xlIGN4PSIxMDYiIGN5PSI0MzciIHI9IjE1Ii8+DQoJPC9nPg0KPC9nPg0KPGc+DQoJPGc+DQoJCTxwYXRoIGQ9Ik0zMTYsMGMtOC4yODQsMC0xNSw2LjcxNi0xNSwxNXYzMWMwLDguMjg0LDYuNzE2LDE1LDE1LDE1czE1LTYuNzE2LDE1LTE1VjE1QzMzMSw2LjcxNiwzMjQuMjg0LDAsMzE2LDB6Ii8+DQoJPC9nPg0KPC9nPg0KPGc+DQoJPGc+DQoJCTxwYXRoIGQ9Ik0yNTIuMzYsNjYuMTQ4bC0yMS4yMTMtMjEuMjEzYy01Ljg1Ny01Ljg1OC0xNS4zNTUtNS44NTgtMjEuMjEzLDBjLTUuODU4LDUuODU4LTUuODU4LDE1LjM1NSwwLDIxLjIxM2wyMS4yMTMsMjEuMjEzDQoJCQljNS44NTcsNS44NTcsMTUuMzU2LDUuODU4LDIxLjIxMywwQzI1OC4yMTgsODEuNTAzLDI1OC4yMTgsNzIuMDA2LDI1Mi4zNiw2Ni4xNDh6Ii8+DQoJPC9nPg0KPC9nPg0KPGc+DQoJPGc+DQoJCTxwYXRoIGQ9Ik00MjIuMDY2LDQ0LjkzNWMtNS44NTctNS44NTgtMTUuMzU1LTUuODU4LTIxLjIxMywwTDM3OS42NCw2Ni4xNDdjLTUuODU4LDUuODU4LTUuODU4LDE1LjM1NSwwLDIxLjIxMw0KCQkJYzUuODU3LDUuODU4LDE1LjM1NSw1Ljg1OSwyMS4yMTMsMC4wMDFsMjEuMjEzLTIxLjIxM0M0MjcuOTI0LDYwLjI5LDQyNy45MjQsNTAuNzkzLDQyMi4wNjYsNDQuOTM1eiIvPg0KCTwvZz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjwvc3ZnPg0K',
        );
        $src              = $icons_collection[ $name ];

        if ( $src ) {
            return "<img $class src=\"data:image/svg+xml;base64,$src\" width=\"$size[0]\" height=\"$size[1]\" />";
        }
        else {
            return '';
        }
    }

    /**
     * Writing a log file
     *
     * @param $log
     */
    public function write_log( $log )
    {
        if ( substr( $_SERVER[ 'SERVER_NAME' ], -4 ) == '.loc' ) {
            file_put_contents( 'd:\temp\imca.txt', date( "d.m.Y H:i" ) . ': ' . print_r( $log, true ) . "\n", FILE_APPEND );
        }
    }

}

