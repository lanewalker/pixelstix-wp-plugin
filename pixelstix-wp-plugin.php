<?php
/*
Plugin Name: PixelStix Mural Platform by PixelStix
Plugin URI: https://www.pixelstix.com/pixelstix-mural-platform-wp-plugin
Description: Import the features of the PixelStix Mural Platform into your wordpress site (APK Key required)
Version: 1.0
Author: Matthew Walker
Author URI: http://www.pixelstix.com/
License: GPLv2 or later
Text Domain: pixelstix
*/

//BEGIN SETTINGS

//REGISTER THE SETTINGS PAGE
function pixelstix_add_settings_page() {
    add_options_page( 'PixelStix page', 'PixelStix', 'manage_options', 'pixelstix-example-plugin', 'pixelstix_render_plugin_settings_page' );
}
add_action( 'admin_menu', 'pixelstix_add_settings_page' );

//REGISTER SETTINGS
function pixelstix_register_settings()
{
    register_setting('pixelstix_plugin_options', 'pixelstix_plugin_options', 'pixelstix_plugin_options_validate');
    add_settings_section('api_settings', 'API Settings', 'pixelstix_plugin_api_section_text', 'pixelstix_plugin_api');
    add_settings_section('map_settings','Map Settings','pixelstix_plugin_map_section_text','pixelstix_plugin_map');
    add_settings_section('shortcode_settings','PixelStix Shortcodes','pixelstix_plugin_shortcode_section_text','pixelstix_plugin_shortcode');
    add_settings_field('pixelstix_plugin_setting_api_key', 'API Key', 'pixelstix_plugin_setting_api_key', 'pixelstix_plugin_api', 'api_settings');
    add_settings_field('pixelstix_plugin_setting_map_type','MAP Type','pixelstix_plugin_setting_map_type','pixelstix_plugin_map','map_settings');
    add_settings_field('pixelstix_plugin_setting_map_shortcode','MAP Shortcode','pixelstix_plugin_setting_map_shortcode','pixelstix_plugin_shortcode','shortcode_settings');
}
add_action('admin_init', 'pixelstix_register_settings');

//SETTINGS FORM DISPLAY
function pixelstix_render_plugin_settings_page() {
    ?>
    <h1>PixelStix Mural Platform by PixelStix</h1>
    <br/>
    <form action="options.php" method="post">
        <?php
        settings_fields('pixelstix_plugin_options');
        do_settings_sections('pixelstix_plugin_api');
        do_settings_sections('pixelstix_plugin_map');
        do_settings_sections('pixelstix_plugin_shortcode');?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save PixelStix Settings'); ?>" />
    </form>
    <?php
}

//API SECTION TEXT
function pixelstix_plugin_api_section_text()
{
    echo '<p>Enter your PixelStix Mural Platform API Key below. <a href="https://www.pixelstix.com/pixelstix-mural-platform-api">For more info click here.</a></p>';
}
//API FIELD
function pixelstix_plugin_setting_api_key()
{
    $options = get_option('pixelstix_plugin_options');
    $api_key=$options['api_key'];
    echo "<input id='pixelstix_plugin_setting_api_key' name='pixelstix_plugin_options[api_key]' type='text' value='".$api_key."' />";
}

//MAP SECTION TEXT
function pixelstix_plugin_map_section_text()
{
    echo '<p>Select a the map software to use when displaying a map of mural locations.</p>';
}
//MAP FIELD
function pixelstix_plugin_setting_map_type()
{
    $options = get_option('pixelstix_plugin_options');
    $map_type=$options['map_type'];
    echo "<select id='pixelstix_plugin_setting_map_type' name='pixelstix_plugin_options[map_type]'><option name='Leaflet'>Leaflet</option></select>";
}

//SHORTCODE SECTION TEXT
function pixelstix_plugin_shortcode_section_text()
{
    echo '<p>Shortcodes listed below can be used to include dynamic mural data into your Pages and Posts</p>';
}
//MAP FIELD
function pixelstix_plugin_setting_map_shortcode()
{
    $options = get_option('pixelstix_plugin_options');
    $map_type=$options['map_type'];
    echo "<input id='pixelstix_plugin_setting_map_shortcode' name='pixelstix_plugin_options[map_shortcode]' type='text' value='[pixelstix_map]' disabled/>";
    echo "<br/><br/>";
    echo "<table style='background: aliceblue;'>";
    echo "<tr style='border:3px dashed black;'><td>required<strong> map_name=</strong></td><td>[name-of-pixelstix-map]</td></tr>";
    echo "<tr style='border:3px dashed black;'><td>optional<strong> size=</strong></td><td>small,medium,large</td></tr>";
    echo "<tr style='border:3px dashed black;'><td>optional<strong> mural_name=</strong></td><td>[name-of-individual-mural]</td></tr>";
    echo "</table>";
    echo "<br/><i>example: [pixelstix_map map_name=\"morningbreath\" size=\"large\" mural_name=\"Morning Breath 2019\"]</i>";
}

//FORM VALIDATION
function pixelstix_plugin_options_validate($input)
{
    $newinput['api_key'] = trim($input['api_key']);
    if (!preg_match('/^[a-z0-9]{16}$/i', $newinput['api_key'])) {
        $newinput['api_key'] = '';
    }
    $newinput['map_type'] = $input['map_type'];

    return $newinput;
}

// END SETTINGS

// BEGIN SHORTCODES
//pixelstix_maps
function pixelstix_maps_shortcode($atts){

    $options = get_option('pixelstix_plugin_options');
    $api_key=$options['api_key'];
    $map_type=$options['map_type'];
    $map_name=$atts["map_name"];
    $size=strtolower($atts["size"]);
    $mural_name=strtolower($atts["mural_name"]);
    $size_css = "";
    $pixelstix_map=[];
    $valid=true;

    //var_dump($atts);

    //deal with required settings/attributes
    if(empty($api_key)){
        $valid=false;
        echo "<i>[pixelstix_map is unable to render without a valid <strong>api key</strong>][go to settings]</i><br/>";
    }
    if(empty($map_type)){
        $valid=false;
        echo "<i>[pixelstix_map is unable to render without a <strong>map type</strong>][go to settings]</i><br/>";
    }
    if(empty($map_name)){
        $valid=false;
        echo "<i>[pixelstix_map is unable to render without a specified <strong>map_name</strong> attribute][edit the shortcode]</i><br/>";
    }

    //other settings/attributes, should default to 'large'
    if($size == "small") {
        $size_css = "height:200px;";
    }
    if($size == "medium") {
        $size_css = "height:400px;";
    }
    if($size == "large" OR empty($size)){
        $size_css = "height:500px;";
    }


    if($valid) {

        //get the lat/lon items for this pixelstix map
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.pixelstix.com/api/v2/public/tags/tag_name/".$map_name."?api_key=".$api_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;
        $response = json_decode($response,true);
        //var_dump($response["data"]["object_voice"][1]["lat"]);

        foreach($response["data"]["object_voice"] as $i=>$item){
            $pixelstix_map[$i]["lat"]=$item["lat"];
            $pixelstix_map[$i]["lon"]=$item["lon"];
            $pixelstix_map[$i]["alias"]=addslashes($item["alias"]);
        }

        //if the mural attribute was specified, attempt to isolate just that mural
        if(!empty($mural_name)){
            $pixelstix_map_buff = [];
            //var_dump($mural_name);
            foreach($pixelstix_map as $map){
                if(strtolower($map["alias"]) == $mural_name){
                    $pixelstix_map_buff[] = $map;
                }
            }
            if(!empty($pixelstix_map_buff)){
                $pixelstix_map=$pixelstix_map_buff;
            }
        }

        //var_dump($pixelstix_map);

        echo ' <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin=""/>';
        echo ' <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>';
        //echo '<script src="'.esc_url(plugins_url("scripts/leaflet-providers.js",__FILE__)).'"></script>';
        echo '<div id="mapid" style="'.$size_css.'"></div>';
        ?>
        <script type="text/javascript">

            var muralmap = L.map('mapid').setView([<?= $pixelstix_map[0]['lat'] ?>,<?= $pixelstix_map[0]['lon'] ?>], 13);
            L.tileLayer(
                "https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png",
                {
                    "attribution": "\u0026copy; Map Data \u003ca href=\"http://www.openstreetmap.org/copyright\"\u003eOpenStreetMap\u003c/a\u003e \u007C \u0026copy; Mural Data \u003ca href=\"http://www.pixelstix.com/\"\u003ePixelStix\u003c/a\u003e \u007C ",
                    "detectRetina": false,
                    "maxNativeZoom": 18,
                    "maxZoom": 18,
                    "minZoom": 0,
                    "noWrap": false,
                    "opacity": 1,
                    "subdomains": "abc",
                    "tms": false
                }
            ).addTo(muralmap);

            //customizations based on data from mural platform api
            mural_markers = [];
            <?php
                foreach($pixelstix_map as $i=>$mapitem){
                    echo "mural_markers.push( L.marker( [${mapitem['lat']},${mapitem['lon']}] ).bindPopup('".$mapitem['alias']."') );";
                }
            ?>
            var fg = L.featureGroup(mural_markers).addTo(muralmap);
            muralmap.fitBounds(fg.getBounds());

        </script>
        <?php
    }
}
add_shortcode('pixelstix_map','pixelstix_maps_shortcode');



// END SHORTCODES
