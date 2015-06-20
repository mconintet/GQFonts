<?php

/*
Plugin Name: GQFonts
Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
Description: It will use fonts CDN service provided by Qihoo 360 instead of Google when the request ip is belong CN, otherwise it let everything work as normal.
Version:     0.1
Author:      mconintet
Author URI:  http://mconintet.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

require_once('CNIPListMaker.php');

class GQFonts
{
    const name = 'GQFonts';

    public function addMenu()
    {
        add_options_page(
            self::name . ' Settings',
            self::name,
            'manage_options',
            self::name . '-settings',
            array($this, 'settings')
        );
    }

    public function settings()
    {
        $maker = CNIPListMaker::instance();
        ?>

        <div class="wrap">
            <h2><?php echo self::name ?> Settings</h2>

            <h3>Local CN IPs</h3>
            <small>
                The IPs are downloaded from <a href="http://ftp.apnic.net/apnic/stats/apnic/delegated-apnic-latest">APNIC</a>
                and been used to
                compare with the request IPs to tell if they are from CN.
            </small>

            <p>
                <b>Status: </b><?php echo $maker->getStatus(); ?>
            </p>

            <button id="gqf-btnRegenerate">Regenerate</button>
            <script type="text/javascript">
                (function ($) {
                    $('#gqf-btnRegenerate').on('click', function () {
                        var me = $(this);

                        if (confirm('This operation will rewrite all stored CN IPs.\nAre you sure?'))
                            $.ajax({
                                url: '<?php echo admin_url( 'admin.php' ); ?>',
                                type: 'post',
                                data: {
                                    re: 1,
                                    action: 'gqfontRegenerate'
                                },
                                dataType: 'json',
                                beforeSend: function () {
                                    me.attr('disabled', 'disabled');
                                    me.html('Regenerating...');
                                },
                                success: function (json) {
                                    if (json['ok']) {
                                        alert('Congratulations, success to regenerate!');
                                        window.location.reload();
                                    }
                                    else {
                                        alert(json['msg']);
                                    }
                                },
                                error: function (err) {
                                    alert(err);
                                },
                                complete: function () {
                                    me.html('Regenerate');
                                    me.removeAttr('disabled');
                                }
                            });
                    });
                })(jQuery);
            </script>
        </div>

        <?php
    }

    public function regenerate()
    {
        $maker = CNIPListMaker::instance();

        if (isset($_POST['re']) && $_POST['re'] == '1') {
            $ret = [
                'ok' => true,
                'msg' => ''
            ];

            try {
                $maker->run();
            } catch (Exception $e) {
                $ret['ok'] = false;
                $ret['msg'] = $e->getMessage();
            }

            exit(json_encode($ret));
        }
    }

    public function automateScript()
    {
        $maker = CNIPListMaker::instance();
        $remoteIP = $_SERVER['REMOTE_ADDR'];

        if(!$maker->isCN($remoteIP)) return;

        $scripts = wp_scripts();
        foreach ($scripts->registered as &$script) {
            $script->src = str_replace('googleapis', 'useso', $script->src);
        }
    }

    public function automateStyle()
    {
        $maker = CNIPListMaker::instance();
        $remoteIP = $_SERVER['REMOTE_ADDR'];

        if(!$maker->isCN($remoteIP)) return;

        $styles = wp_styles();
        foreach ($styles->registered as &$style) {
            $style->src = str_replace('googleapis', 'useso', $style->src);
        }
    }
}

$gqfonts = new GQFonts();
add_action('admin_menu', [$gqfonts, 'addMenu']);
add_action('admin_action_gqfontRegenerate', [$gqfonts, 'regenerate']);

add_action('wp_print_scripts', [$gqfonts, 'automateScript']);
add_action('wp_print_styles', [$gqfonts, 'automateStyle']);

add_action('admin_print_scripts', [$gqfonts, 'automateScript']);
add_action('admin_print_styles', [$gqfonts, 'automateStyle']);
