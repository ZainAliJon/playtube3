<?php
if (file_exists('./assets/init.php')) {
    require_once('./assets/init.php');
} else {
    die('Please put this file in the home directory !');
}
if (!file_exists('update_langs')) {
    die('Folder ./update_langs is not uploaded and missing, please upload the update_langs folder.');
}

$versionToUpdate = '3.1';
$olderVersion = '3.0.1';
if ($pt->config->version == $versionToUpdate && $pt->config->filesVersion == $pt->config->version) {
    die("Your website is already updated to {$versionToUpdate}, nothing to do.");
}
if ($pt->config->version == $versionToUpdate && $pt->config->filesVersion != $pt->config->version) {
    die("Your website is database is updated to {$versionToUpdate}, but files are not uploaded, please upload all the files and make sure to use SFTP, all files should be overwritten.");
}
if ($pt->config->version < $olderVersion) {
    die("Please update to {$olderVersion} first version by version, your current version is: " . $pt->config->version);
}

$updated = false;
if (!empty($_GET['updated'])) {
    $updated = true;
}
if (!empty($_POST['query'])) {
    $query = mysqli_query($mysqli, base64_decode($_POST['query']));
    if ($query) {
        $data['status'] = 200;
    } else {
        $data['status'] = 400;
        $data['error']  = mysqli_error($mysqli);
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}
function updateLangs($lang) {
    global $sqlConnect;
    if (!file_exists("update_langs/{$lang}.txt")) {
        $filename = "update_langs/unknown.txt";
    } else {
        $filename = "update_langs/{$lang}.txt";
    }
    // Temporary variable, used to store current query
    $templine = '';
    // Read in entire file
    $lines    = file($filename);
    // Loop through each line
    foreach ($lines as $line) {
        // Skip it if it's a comment
        if (substr($line, 0, 2) == '--' || $line == '')
            continue;
        // Add this line to the current segment
        $templine .= $line;
        $query = false;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            $templine = str_replace('`{unknown}`', "`{$lang}`", $templine);
            //echo $templine;
            $query    = mysqli_query($sqlConnect, $templine);
            // Reset temp variable to empty
            $templine = '';
        }
    }
}
if (!empty($_POST['update_langs'])) {
    $data  = array();
    $query = mysqli_query($sqlConnect, "SHOW COLUMNS FROM `langs`");
    while ($fetched_data = mysqli_fetch_assoc($query)) {
        $data[] = $fetched_data['Field'];
    }
    unset($data[0]);
    unset($data[1]);
    unset($data[2]);
    $lang_update_queries = array();
    foreach ($data as $key => $value) {
        updateLangs($value);
    }
    $deleteFile = deleteDirectory("update_langs");

    $configFilePath = 'config.php';

    // Read the content of the file
    $configContent = file_get_contents($configFilePath);

    if (strpos($configContent,'siteEncryptKey') === false) {
        $keyLength = 20;
        $app_key = bin2hex(random_bytes($keyLength));

        // The string to append
        $siteEncryptKey = "'".$app_key."';";

        // Append the string before the closing PHP tag
        $configContent = preg_replace('/\?>/', "\n\$siteEncryptKey = $siteEncryptKey\n?>", $configContent);

        // Write the updated content back to the file
        file_put_contents($configFilePath, $configContent);

        foreach ($pt->encryptedKeys as $key => $value) {
            if (in_array($value, array_keys((array) $pt->config)) && !empty($pt->config->{$value}) && strpos($pt->config->{$value},'$Ap1_') === false) {
                $encryptedValue = '$Ap1_'.openssl_encrypt($pt->config->{$value}, "AES-128-ECB", $app_key);
                $db->where('name',$value)->update(T_CONFIG,[
                    'value' => $encryptedValue
                ]);
            }
        }
    }

    $array              = [];
    $files             = scandir('./admin-panel/pages');
    $not_allowed_files = array(
        'edit-custom-page',
        'edit-lang',
        'edit-movie',
        'edit-profile-field',
        'edit-terms-pages',
        'manage-permissions'
    );
    foreach ($files as $key => $file) {
        if (file_exists('./admin-panel/pages/' . $file . '/content.html') && !in_array($file, $not_allowed_files)) {
            $string = file_get_contents('./admin-panel/pages/' . $file . '/content.html');
            preg_match_all("@(?s)<h2([^<]*)>([^<]*)<\/h2>@", $string, $matches1);
            if (!empty($matches1) && !empty($matches1[2])) {
                foreach ($matches1[2] as $key => $title) {

                    $page_title = '';
                    preg_match_all("@(?s)<h2([^<]*)>([^<]*)<\/h2>@", $string, $matches3);
                    if (!empty($matches3) && !empty($matches3[2])) {
                        foreach ($matches3[2] as $key => $title2) {
                            $page_title = $title2;
                            break;
                        }
                    }

                    $array[] = [
                        'link' => $file,
                        'title' => $title,
                        'page_title' => $page_title,
                    ];
                }
            }


            preg_match_all("@(?s)<label([^<]*)>([^<]*)<\/label>@", $string, $matches2);
            if (!empty($matches2) && !empty($matches2[2])) {
                foreach ($matches2[2] as $key => $lable) {
                    $page_title = '';
                    preg_match_all("@(?s)<h2([^<]*)>([^<]*)<\/h2>@", $string, $matches3);
                    if (!empty($matches3) && !empty($matches3[2])) {
                        foreach ($matches3[2] as $key => $title2) {
                            $page_title = $title2;
                            break;
                        }
                    }

                    $array[] = [
                        'link' => $file,
                        'title' => $lable,
                        'page_title' => $page_title,
                    ];
                }
            }
        }
    }

    $arrayCode = var_export($array, true);

    // Write the PHP code to a file
    file_put_contents('./admin-panel/search-result.php', "<?php\n\n\$pages_search = $arrayCode;\n");

    $db->where('name', 'version')->update(T_CONFIG, ['value' => $versionToUpdate]);
    $name = md5(microtime()) . '_updated.php';
    rename('update.php', $name);
}
?>
<html>
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
      <meta name="viewport" content="width=device-width, initial-scale=1"/>
      <title>Updating PlayTube</title>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
      <style>
         @import url('https://fonts.googleapis.com/css?family=Roboto:400,500');
         @media print {
            .wo_update_changelog {max-height: none !important; min-height: !important}
            .btn, .hide_print, .setting-well h4 {display:none;}
         }
         * {outline: none !important;}
         body {background: #f3f3f3;font-family: 'Roboto', sans-serif;}
         .light {font-weight: 400;}
         .bold {font-weight: 500;}
         .btn {height: 52px;line-height: 1;font-size: 16px;transition: all 0.3s;border-radius: 2em;font-weight: 500;padding: 0 28px;letter-spacing: .5px;}
         .btn svg {margin-left: 10px;margin-top: -2px;transition: all 0.3s;vertical-align: middle;}
         .btn:hover svg {-webkit-transform: translateX(3px);-moz-transform: translateX(3px);-ms-transform: translateX(3px);-o-transform: translateX(3px);transform: translateX(3px);}
         .btn-main {color: #ffffff;background-color: #00BCD4;border-color: #00BCD4;}
         .btn-main:disabled, .btn-main:focus {color: #fff;}
         .btn-main:hover {color: #ffffff;background-color: #0dcde2;border-color: #0dcde2;box-shadow: -2px 2px 14px rgba(168, 72, 73, 0.35);}
         svg {vertical-align: middle;}
         .main {color: #00BCD4;}
         .wo_update_changelog {
          border: 1px solid #eee;
          padding: 10px !important;
         }
         .content-container {display: -webkit-box; width: 100%;display: -moz-box;display: -ms-flexbox;display: -webkit-flex;display: flex;-webkit-flex-direction: column;flex-direction: column;min-height: 100vh;position: relative;}
         .content-container:before, .content-container:after {-webkit-box-flex: 1;box-flex: 1;-webkit-flex-grow: 1;flex-grow: 1;content: '';display: block;height: 50px;}
         .wo_install_wiz {position: relative;background-color: white;box-shadow: 0 1px 15px 2px rgba(0, 0, 0, 0.1);border-radius: 10px;padding: 20px 30px;border-top: 1px solid rgba(0, 0, 0, 0.04);}
         .wo_install_wiz h2 {margin-top: 10px;margin-bottom: 30px;display: flex;align-items: center;}
         .wo_install_wiz h2 span {margin-left: auto;font-size: 15px;}
         .wo_update_changelog {padding:0;list-style-type: none;margin-bottom: 15px;max-height: 440px;overflow-y: auto; min-height: 440px;}
         .wo_update_changelog li {margin-bottom:7px; max-height: 20px; overflow: hidden;}
         .wo_update_changelog li span {padding: 2px 7px;font-size: 12px;margin-right: 4px;border-radius: 2px;}
         .wo_update_changelog li span.added {background-color: #4CAF50;color: white;}
         .wo_update_changelog li span.changed {background-color: #e62117;color: white;}
         .wo_update_changelog li span.improved {background-color: #9C27B0;color: white;}
         .wo_update_changelog li span.compressed {background-color: #795548;color: white;}
         .wo_update_changelog li span.fixed {background-color: #2196F3;color: white;}
         input.form-control {background-color: #f4f4f4;border: 0;border-radius: 2em;height: 40px;padding: 3px 14px;color: #383838;transition: all 0.2s;}
input.form-control:hover {background-color: #e9e9e9;}
input.form-control:focus {background: #fff;box-shadow: 0 0 0 1.5px #a84849;}
         .empty_state {margin-top: 80px;margin-bottom: 80px;font-weight: 500;color: #6d6d6d;display: block;text-align: center;}
         .checkmark__circle {stroke-dasharray: 166;stroke-dashoffset: 166;stroke-width: 2;stroke-miterlimit: 10;stroke: #7ac142;fill: none;animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;}
         .checkmark {width: 80px;height: 80px; border-radius: 50%;display: block;stroke-width: 3;stroke: #fff;stroke-miterlimit: 10;margin: 100px auto 50px;box-shadow: inset 0px 0px 0px #7ac142;animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;}
         .checkmark__check {transform-origin: 50% 50%;stroke-dasharray: 48;stroke-dashoffset: 48;animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;}
         @keyframes stroke { 100% {stroke-dashoffset: 0;}}
         @keyframes scale {0%, 100% {transform: none;}  50% {transform: scale3d(1.1, 1.1, 1); }}
         @keyframes fill { 100% {box-shadow: inset 0px 0px 0px 54px #7ac142; }}
      </style>
   </head>
   <body>
      <div class="content-container container">
         <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-10">
               <div class="wo_install_wiz">
                 <?php if ($updated == false) { ?>
                  <div>
                     <h2 class="light">Update to v<?php echo $versionToUpdate?> </span></h2>
                     <div class="alert alert-danger">
                       <strong>Important:</strong> Don't run the update process before all the files were uploaded to your server, please make sure all files are uploaded to your server then click the update button below.
                     </div>
                     <div class="alert alert-info">
                     <strong>Note:</strong> In case you want to use YouTube Shorts or TikTok import feature, you need to create an account in <a href="https://rapidapi.com/" target="_blank">Rapid API</a> and subscribe to these libraries <a href="https://rapidapi.com/yi005/api/tiktok-video-no-watermark2" target="_blank">TikTok</a>, <a href="https://rapidapi.com/DataFanatic/api/youtube-media-downloader" target="_blank">YouTube Shorts</a>.
                     </div>
                     <div class="setting-well">
                        <h4>Changelog</h4>
                        <ul class="wo_update_changelog">
                        <li>[Added] Braintree, PayFast payment gateways.</li>
                            <li>[Added] 50+ new APIs for future mobile updates.</li>
                            <li>[Added] switch accounts system.</li>
                            <li>[Fixed] date and time in message were not active.</li>
                            <li>[Fixed] can't upload a short if movie system is disabled.</li>
                            <li>[Fixed] When new member register and use special characters like &^%$#@ etc in password, they can't login.</li>
                            <li>[Fixed] video cropping not working when uploading shorts.</li>
                            <li>[Fixed] youtube shorts import not working.</li>
                            <li>[Fixed] Pro packages are showing as monthly with every package.</li>
                            <li>[Fixed] tiktok import was not working</li>
                            <li>[Fixed] OG meta tags for shorts are not working when you share on other platforms. </li>
                            <li>[Fixed] contact us message didn't contain any names or user info.</li>
                            <li>[Fixed] cronjob.php file is converting videos multiple times.</li>
                            <li>[Fixed] clean dead videos was not working well.</li>
                            <li>[Fixed] articles share count is not increasing with share on social media etc.</li>
                            <li>[Fixed] movies upload system getting stuck sometimes.</li>
                            <li>[Fixed] 10+ other minor bugs.</li>
                            <li>[Fixed] the major slow down speed on top videos page.</li>
                        </ul>
                        <p class="hide_print">Note: The update process might take few minutes.</p>
                        <p class="hide_print">Important: If you got any fail queries, please copy them, open a support ticket and send us the details.</p>
                        <br>
                             <button class="pull-right btn btn-default" onclick="window.print();">Share Log</button>
                             <button type="button" class="btn btn-main" id="button-update">
                             Update
                             <svg viewBox="0 0 19 14" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                                <path fill="currentColor" d="M18.6 6.9v-.5l-6-6c-.3-.3-.9-.3-1.2 0-.3.3-.3.9 0 1.2l5 5H1c-.5 0-.9.4-.9.9s.4.8.9.8h14.4l-4 4.1c-.3.3-.3.9 0 1.2.2.2.4.2.6.2.2 0 .4-.1.6-.2l5.2-5.2h.2c.5 0 .8-.4.8-.8 0-.3 0-.5-.2-.7z"></path>
                             </svg>
                          </button>
                     </div>
                     <?php }?>
                     <?php if ($updated == true) { ?>
                      <div>
                        <div class="empty_state">
                           <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                              <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                              <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                           </svg>
                           <p>Congratulations, you have successfully updated your site. Thanks for choosing PlayTube.</p>
                           <br>
                           <a href="<?php echo $wo['config']['site_url'] ?>" class="btn btn-main" style="line-height:50px;">Home</a>
                        </div>
                     </div>
                     <?php }?>
                  </div>
               </div>
            </div>
            <div class="col-md-1"></div>
         </div>
      </div>
   </body>
</html>
<script>
var queries = [
    "INSERT INTO `config` (`name`, `value`) VALUES ('rapid_api', '');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('payfast_payment', 'no');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('payfast_mode', 'sandbox');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('payfast_merchant_id', '');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('payfast_merchant_key', '');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('braintree_payment', 'no');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('braintree_mode', 'sandbox');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('braintree_merchant_id', '');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('braintree_public_key', '');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('braintree_private_key', '');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('switch_account', 'on');",
    "INSERT INTO `config` (`name`, `value`) VALUES ('switch_account_counts', '3');",
    "ALTER TABLE `views` CHANGE `time` `time` INT(11) NOT NULL DEFAULT '0';",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'week');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'free');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'you_do_not_permission');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'video_url_changed_successfully');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'payfast');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'braintree');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'switch_account');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'add_account');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'braintree_not_active');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_m');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_h');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_hrs');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_d');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_w');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_y');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, '_time_yrs');",
    "INSERT INTO `langs` (`id`, `lang_key`) VALUES (NULL, 'now');",
];

$('#input_code').bind("paste keyup input propertychange", function(e) {
    if (isPurchaseCode($(this).val())) {
        $('#button-update').removeAttr('disabled');
    } else {
        $('#button-update').attr('disabled', 'true');
    }
});

function isPurchaseCode(str) {
    var patt = new RegExp("(.*)-(.*)-(.*)-(.*)-(.*)");
    var res = patt.test(str);
    if (res) {
        return true;
    }
    return false;
}

$(document).on('click', '#button-update', function(event) {
    if ($('body').attr('data-update') == 'true') {
        window.location.href = '<?php echo $site_url?>';
        return false;
    }
    $(this).attr('disabled', true);
    $('.wo_update_changelog').html('');
    $('.wo_update_changelog').css({
        background: '#1e2321',
        color: '#fff'
    });
    $('.setting-well h4').text('Updating..');
    $(this).attr('disabled', true);
    RunQuery();
});

var queriesLength = queries.length;
var query = queries[0];
var count = 0;
function b64EncodeUnicode(str) {
    // first we use encodeURIComponent to get percent-encoded UTF-8,
    // then we convert the percent encodings into raw bytes which
    // can be fed into btoa.
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
    }));
}
function RunQuery() {
    var query = queries[count];
    $.post('?update', {
        query: b64EncodeUnicode(query)
    }, function(data, textStatus, xhr) {
        if (data.status == 200) {
            $('.wo_update_changelog').append('<li><span class="added">SUCCESS</span> ~$ mysql > ' + query + '</li>');
        } else {
            $('.wo_update_changelog').append('<li><span class="changed">FAILED</span> ~$ mysql > ' + query + '</li>');
        }
        count = count + 1;
        if (queriesLength > count) {
            setTimeout(function() {
                RunQuery();
            }, 1500);
        } else {
            $('.wo_update_changelog').append('<li><span class="added">Updating Langauges & Categories</span> ~$ languages.sh, Please wait, this might take some time..</li>');
            $.post('?run_lang', {
                update_langs: 'true'
            }, function(data, textStatus, xhr) {
              $('.wo_update_changelog').append('<li><span class="fixed">Finished!</span> ~$ Congratulations! you have successfully updated your site. Thanks for choosing PlayTube.</li>');
              $('.setting-well h4').text('Update Log');
              $('#button-update').html('Home <svg viewBox="0 0 19 14" xmlns="http://www.w3.org/2000/svg" width="18" height="18"> <path fill="currentColor" d="M18.6 6.9v-.5l-6-6c-.3-.3-.9-.3-1.2 0-.3.3-.3.9 0 1.2l5 5H1c-.5 0-.9.4-.9.9s.4.8.9.8h14.4l-4 4.1c-.3.3-.3.9 0 1.2.2.2.4.2.6.2.2 0 .4-.1.6-.2l5.2-5.2h.2c.5 0 .8-.4.8-.8 0-.3 0-.5-.2-.7z"></path> </svg>');
              $('#button-update').attr('disabled', false);
              $(".wo_update_changelog").scrollTop($(".wo_update_changelog")[0].scrollHeight);
              $('body').attr('data-update', 'true');
            });
        }
        $(".wo_update_changelog").scrollTop($(".wo_update_changelog")[0].scrollHeight);
    });
}
</script>
