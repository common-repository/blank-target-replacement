<?php
/*********************************************************************************

Plugin Name: Blank Target Replacement
Plugin URI: http://www.nickifaulk.com/free-wordpress-plugins/
Description: This plugin allows you to mark any (or all) of your Blogroll links as 'external' and implements a small bit of javascript to open links in new windows. Also marks all links in posts as 'external' (kudos to Mdkart's Add Lightbox for the idea and code!).
Author: Nicki Faulk
Author URI: http://www.nickifaulk.com/
Version: 1.2
Disclaimer: Use at your own risk. No warranty expressed or implied is provided. There is no guarantee that this will work for your version of WordPress, I wrote this out of need for myself and am sharing in the hopes someone else finds it useful.

*********************************************************************************/

/***** COPYRIGHT/PERMISSION NOTICE **********************************************

You are free: 
 * to Share - to copy, distribute and transmit the work
 * to Remix - to adapt the work
 
Under the following conditions:
	 * Attribution. You must attribute the work in the manner specified by the author or licensor (but not in any way that suggests that they endorse you or your use of the work).
	 * Noncommercial. You may not use this work for commercial purposes.
	 * Share Alike. If you alter, transform, or build upon this work, you may distribute the resulting work only under the same or similar license to this one.
 * For any reuse or distribution, you must make clear to others the license terms of this work. The best way to do this is with a link to this web page.
 * Any of the above conditions can be waived if you get permission from the copyright holder.
 * Nothing in this license impairs or restricts the author's moral rights.
 
You can view a copy of this license here: http://creativecommons.org/licenses/by-nc-sa/3.0/

*********************************************************************************/

/***** INSTRUCTIONS **************************************************************

1. Upload the blank-target-replace folder to WordPress plugins directory and activate on the Plugins page in WP-Admin
2. Go to Manage > External Links
3. Choose the links you want to open in a new window, or tick the top checkbox in the grey bar to check all links as external.  Click the "Mark Links External" button at the very bottom of the page.
4. That's it! Links in your blog entries will automatically have rel="external" added.

*********************************************************************************/

function rel_external_replace()
{ ?>
    <script type="text/javascript">
	//<![CDATA[
		function externalLinks() {
			 // Courtesy of http://www.sitepoint.com/article/standards-compliant-world/3
			 // Use rel="external" with this script to open links in a new window
			 if (!document.getElementsByTagName) return;
			 var anchors = document.getElementsByTagName("a");
			 for (var i=0; i<anchors.length; i++) {
				 var anchor = anchors[i];
				 if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "external")
					 anchor.target = "_blank";
			 }
		}
		window.onload = externalLinks;
	//]]>
    </script>
<?php 
}

add_action('wp_footer', 'rel_external_replace');

if (!function_exists('array_combine')) {
    require_once dirname(__FILE__) . '/array_combine.php';

    function array_combine($keys, $values)
    {
        return php_compat_array_combine($keys, $values);
    }
}

add_action('admin_menu',    'external_links_admin_menu');
add_filter('get_bookmarks', 'external_links_get_bookmarks', 10, 2);
add_filter('the_content', 'addexternalrel_replace');

function external_links_admin_menu()
{
    add_submenu_page('edit.php', 'External Links', 'External Links', 10, 'link-external', 'external_links_manage');
}

function external_links_manage()
{
    if (isset($_POST['externalbookmarks'])) {
        check_admin_referer('external_links_manage');

        if (isset($_POST['linkcheck']) && is_array($_POST['linkcheck'])) {
            $external = array_combine($_POST['linkcheck'], $_POST['linkcheck']);
        } else {
            $external = array();
        }
        update_option('external_links', serialize($external));

        echo '<div style="background-color: rgb(207, 235, 247);" id="message" class="updated fade"><p>' . count($external) . ' links marked external.</p></div>' . "\n";
    }

    $sExternalLinks = get_option("external_links");
    if (!$sExternalLinks) {
        $sExternalLinks = serialize(array());
    }
    $uExternalLinks = unserialize($sExternalLinks);

    $links = get_bookmarks();
    ?>
    <script type="text/javascript">
    <!--
    function checkAll(form)
    {
        for (i = 0, n = form.elements.length; i < n; i++) {
            if(form.elements[i].type == "checkbox") {
                if(form.elements[i].checked == true)
                    form.elements[i].checked = false;
                else
                    form.elements[i].checked = true;
            }
        }
    }
    //-->
    </script>

    <div class="wrap">

    <h2>External Links</h2>

    <form id="links" name="pages-form" action="edit.php?page=link-external" method="post">
    <?php if (function_exists('wp_nonce_field')) { wp_nonce_field('external_links_manage'); } ?>
    <table class="widefat">
    <thead>
    <tr>
        <th width="45%">Name</th>
        <th>URL</th>
        <th style="text-align: center"><input type="checkbox" onclick="checkAll(document.getElementById('links'));" /></th>
    </tr>
    </thead>
    <tbody><?php
    $alt = false;
    foreach ($links as $link) {
        $short_url = str_replace('http://', '', $link->link_url);
        $short_url = str_replace('www.', '', $short_url);
        if ('/' == substr($short_url, -1)) {
            $short_url = substr($short_url, 0, -1);
        }
        if (strlen($short_url) > 35) {
            $short_url = substr($short_url, 0, 32).'...';
        }

        echo "    <tr valign=\"middle\"" . ($alt ? ' class="alternate"' : '') . ">\n";
        echo "        <td><strong>{$link->link_name}</strong><br />" . $link->link_description . "</td>\n";
        echo "        <td><a href=\"{$link->link_url}\" title=\"".sprintf(__('Visit %s'), $link->link_name)."\">{$short_url}</a></td>\n";
        echo "        <td align=\"center\"><input type=\"checkbox\" name=\"linkcheck[]\" value=\"{$link->link_id}\"" . (isset($uExternalLinks[$link->link_id]) ? ' checked="checked"' : '') . " /></td>\n";
        echo "    </tr>\n";

        $alt = !$alt;
    }
    ?>
    </tbody>
    </table>

    <p class="submit"><input type="submit" class="button" name="externalbookmarks" id="externalbookmarks" value="Mark Links External &raquo;" /></p>
    </form>

    </div>
    <?php
}

function external_links_get_bookmarks($links, $args)
{
    $sExternalLinks = get_option("external_links");
    if (!$sExternalLinks) {
        $sExternalLinks = serialize(array());
    }
    $uExternalLinks = unserialize($sExternalLinks);

    foreach (array_keys($links) as $i) {
        if (isset($uExternalLinks[$links[$i]->link_id])) {
            $links[$i]->link_rel .= ' external';

           $links[$i]->link_rel = trim($links[$i]->link_rel);
        }
    }

    return $links;
}

function addexternalrel_replace ($content)
{   global $post;
	$pattern = "/<a href=/i";
    $replacement = '<a rel="external" href=';
    $content = preg_replace($pattern, $replacement, $content);
    return $content;
}

?>