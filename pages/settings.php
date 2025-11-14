<?php


$content = '';
$buttons = '';

if (!rex_addon::get('yrewrite')->isAvailable()) {
    $baseurl = rex::getServer();
} else {
    $baseurl = rex_yrewrite::getCurrentDomain()->getUrl();
}


$func = rex_request('func', 'string');

// Konfiguration speichern
if ($func == 'update') {

    $this->setConfig(rex_post('settings', [
        // ====================================================== Generic feed options
        // Your feed's title
        ['feed_title', 'string'],
        // 'More info' link for your feed
        ['feed_link', 'string'],
        // Brief description
        ['feed_description', 'string'],
        // Copyright / license information
        ['feed_item_additions', 'string'],
        // is added to every episode description by default, mainly for RSS
        ['feed_license', 'string'],
        // How often feed readers check for new material (in seconds) -- mostly ignored by readers
        ['feed_mnemonic', 'string'],
        // Short tag for Podcast Name to be displayed with the Episode number
        ['feed_ttl', 'string'],
        // Language locale of your feed, eg en-us, de, fr etc. See http://www.rssboard.org/rss-language-codes
        ['feed_lang', 'string'],
        // ============================================== iTunes-specific feed options
        // Feed author's contact email address
        ['feed_author', 'string'],
        // If your feed contains explicit material or not (yes, no, clean)
        ['feed_explicit', 'string'],
        // Url of a 170x170 .png image to be used on the iTunes page
        ['feed_image', 'string'],
        // in the more info section of the feed
        ['feed_email', 'string'],
        // iTunes major category of your feed
        ['feed_category', 'string'],
        // iTunes minor category of your feed
        ['feed_subcategory', 'string'],
        //other
        ['stats_rss_active', 'string'],
        ['stats_prefix', 'string'],
        ['stats_rss_id', 'string'],
        ['rss_feed_id', 'string'],
        ['detail_id', 'string'],
        ['feed_subtitle', 'string'],
        ['feed_keywords', 'string'],
        ['feed_owner', 'string'],
        // ============================================== Server Statistics options
        ['stats_enabled', 'string'],
        ['stats_provider', 'string'],
        ['stats_path', 'string']
        // END OF CONFIGURATION VARIABLES
        // TODO: <atom:link rel="payment" title="Flattr this!" href="https://flattr.com/submit/auto?user_id=redaxo&amp;language=de_DE&amp;" type="text/html" />


    ]));

    echo rex_view::success($this->i18n('config_saved'));
}

// Config-Werte bereitstellen
$Values = array();
$Values['feed_title'] = $this->getConfig('feed_title');
$Values['feed_link'] = $this->getConfig('feed_link');
$Values['feed_subtitle'] = $this->getConfig('feed_subtitle');
$Values['feed_description'] = $this->getConfig('feed_description');
$Values['feed_item_additions'] = $this->getConfig('feed_item_additions');
$Values['feed_keywords'] = $this->getConfig('feed_keywords');
$Values['feed_license'] = $this->getConfig('feed_license');
$Values['feed_ttl'] = $this->getConfig('feed_ttl');
$Values['feed_mnemonic'] = $this->getConfig('feed_mnemonic');
$Values['feed_lang'] = $this->getConfig('feed_lang');
$Values['feed_author'] = $this->getConfig('feed_author');
$Values['feed_owner'] = $this->getConfig('feed_owner');
$Values['feed_explicit'] = $this->getConfig('feed_explicit');
$Values['feed_image'] = $this->getConfig('feed_image');
$Values['feed_email'] = $this->getConfig('feed_email');
$Values['feed_category'] = $this->getConfig('feed_category');
$Values['feed_subcategory'] = $this->getConfig('feed_subcategory');
$Values['rss_feed_id'] = $this->getConfig('rss_feed_id');
$Values['detail_id'] = $this->getConfig('detail_id');
$Values['stats_rss_active'] = $this->getConfig('stats_rss_active');
$Values['stats_prefix'] = $this->getConfig('stats_prefix');
$Values['stats_rss_id'] = $this->getConfig('stats_rss_id');
$Values['stats_enabled'] = $this->getConfig('stats_enabled');
$Values['stats_provider'] = $this->getConfig('stats_provider', 'webalizer');
$Values['stats_path'] = $this->getConfig('stats_path');


$content .= '<fieldset><legend>' . $this->i18n('basic_settings') . '</legend>';


// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_title">' . htmlspecialchars_decode($this->i18n('feed_title')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_title" name="settings[feed_title]" value="' . $Values['feed_title'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_title_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_subtitle">' . htmlspecialchars_decode($this->i18n('feed_subtitle')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_subtitle" name="settings[feed_subtitle]" value="' . $Values['feed_subtitle'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_subtitle_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_keywords">' . htmlspecialchars_decode($this->i18n('feed_keywords')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_ttl" name="settings[feed_keywords]" value="' . $Values['feed_keywords'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_keywords_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');


// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_link">' . htmlspecialchars_decode($this->i18n('feed_link')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_link" name="settings[feed_link]" value="' . $Values['feed_link'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_link_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Textarea
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_description">' . htmlspecialchars_decode($this->i18n('feed_description')) . '</label>';
$n['field'] = '<textarea class="form-control" type="text" id="feed_description" style="height:4em !important;" name="settings[feed_description]">' . $Values['feed_description'] . '</textarea>';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_description_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Textarea
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_item_additions">' . htmlspecialchars_decode($this->i18n('feed_item_additions')) . '</label>';
$n['field'] = '<textarea class="form-control" type="text" id="feed_item_additions" style="height:4em !important;" name="settings[feed_item_additions]">' . $Values['feed_item_additions'] . '</textarea>';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_item_additions_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');


// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_mnemonic">' . htmlspecialchars_decode($this->i18n('feed_mnemonic')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_mnemonic" name="settings[feed_mnemonic]" value="' . $Values['feed_mnemonic'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_mnemonic_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_license">' . htmlspecialchars_decode($this->i18n('feed_license')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_license" name="settings[feed_license]" value="' . $Values['feed_license'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_license_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_lang">' . htmlspecialchars_decode($this->i18n('feed_lang')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_lang" name="settings[feed_lang]" value="' . $Values['feed_lang'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_lang_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');


/// FIELDSET
$content .= '</fieldset><fieldset><legend>' . $this->i18n('itunes_settings') . '</legend>';


// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_author">' . htmlspecialchars_decode($this->i18n('feed_author')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_author" name="settings[feed_author]" value="' . $Values['feed_author'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_author_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_owner">' . htmlspecialchars_decode($this->i18n('feed_owner')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_owner" name="settings[feed_owner]" value="' . $Values['feed_owner'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_owner'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Select
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_explicit">' . $this->i18n('feed_explicit') . '</label>';
$select = new rex_select();
$select->setId('feed_explicit');
$select->setAttribute('class', 'feed_explicit');
$select->setName('settings[feed_explicit]');
$select->addOption('yes','yes');
$select->addOption('no','no');
$select->addOption('clean','clean');
$select->setSelected($this->getConfig('feed_explicit'));
$n['field'] = $select->get();
$n['note'] = htmlspecialchars_decode($this->i18n('feed_explicit_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Media
$formElements = [];
$n = [];
$n['label'] = '<label for="REX_MEDIA_1">' . htmlspecialchars_decode($this->i18n('feed_image')) . '</label>';
$n['field'] = '
<div class="rex-js-widget rex-js-widget-media">
    <div class="input-group">
        <input class="form-control" type="text" name="settings[feed_image]" value="' . $Values['feed_image'] . '" id="REX_MEDIA_1" readonly="">
        <span class="input-group-btn">
        <a href="#" class="btn btn-popup" onclick="openREXMedia(1);return false;" title="' . $this->i18n('config_selectmedia') . '">
            <i class="rex-icon rex-icon-open-mediapool"></i>
        </a>
        <a href="#" class="btn btn-popup" onclick="addREXMedia(1);return false;" title="' . $this->i18n('config_addmedia') . '">
            <i class="rex-icon rex-icon-add-media"></i>
        </a>
        <a href="#" class="btn btn-popup" onclick="deleteREXMedia(1);return false;" title="' . $this->i18n('config_deletemedia') . '">
            <i class="rex-icon rex-icon-delete-media"></i>
        </a>
        <a href="#" class="btn btn-popup" onclick="viewREXMedia(1);return false;" title="' . $this->i18n('config_showmedia') . '">
            <i class="rex-icon rex-icon-view-media"></i>
        </a>
    </div>
 </div>
';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_image_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');


// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_email">' . htmlspecialchars_decode($this->i18n('feed_email')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_email" name="settings[feed_email]" value="' . $Values['feed_email'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_email_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_category">' . htmlspecialchars_decode($this->i18n('feed_category')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_category" name="settings[feed_category]" value="' . $Values['feed_category'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_category_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="feed_subcategory">' . htmlspecialchars_decode($this->i18n('feed_subcategory')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="feed_subcategory" name="settings[feed_subcategory]" value="' . $Values['feed_subcategory'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('feed_subcategory_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');


/*

// Checkbox
$formElements = [];
$n = [];
$n['label'] = '<label for="showscrollbar">' . htmlspecialchars_decode($this->i18n('config_showscrollbar')) . '</label>';
$n['field'] = '<input type="checkbox" id="showscrollbar" name="settings[showscrollbar]"' . (!empty($Values['showscrollbar']) && $Values['showscrollbar'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');
*/

/// FIELDSET
$content .= '</fieldset><fieldset><legend>' . $this->i18n('other_settings') . '</legend>';

// Seitenauswahl
if($Values['rss_feed_id']) $validate_link = $baseurl.rex_getUrl($Values['rss_feed_id']);
$formElements = [];
$n = [];
$n['label'] = '<label for="REX_LINK_SELECT_1">' . $this->i18n('config_rss_feed_id') . '</label>';
$n['field'] = rex_var_link::getWidget(1, 'settings[rss_feed_id]', $this->getConfig('rss_feed_id'));
$n['note'] = htmlspecialchars_decode($this->i18n('feed_rss_feed_id_help'))
            ."<br>Check Feed URL <b>($validate_link)</b> -- <a target='_blank' href='https://podba.se/validate/?url=$validate_link'>Podbase</a> -- <a target='_blank' href='http://castfeedvalidator.com/?url=$validate_link'>Castfeed</a>";
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');
// Seitenauswahl
if($Values['detail_id']) $validate_link = $baseurl.rex_getUrl($Values['detail_id']);
$formElements = [];
$n = [];
$n['label'] = '<label for="REX_LINK_SELECT_2">' . $this->i18n('detail_id') . '</label>';
$n['field'] = rex_var_link::getWidget(2, 'settings[detail_id]', $this->getConfig('detail_id'));
$n['note'] = htmlspecialchars_decode($this->i18n('detail_id_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Select
$formElements = [];
$n = [];
$n['label'] = '<label for="setting_limit">' . $this->i18n('setting_limit') . '</label>';
$select = new rex_select();
$select->setId('setting_limit');
$select->setAttribute('class', 'setting_limit');
$select->setName('settings[setting_limit]');
$select->addOption('unlimitiert','');
$select->addOption('10','10');
$select->addOption('25','25');
$select->addOption('50','50');
$select->addOption('75','75');
$select->addOption('100','100');
$select->addOption('150','150');
$select->addOption('200','200');
$select->addOption('300','300');
$select->addOption('500','500');
$select->setSelected($this->getConfig('setting_limit'));
$n['field'] = $select->get();
$n['note'] = htmlspecialchars_decode($this->i18n('setting_limit_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');




$content .= '</fieldset>';



/// FIELDSET STATS
$content .= '</fieldset><fieldset><legend>' . $this->i18n('stats_settings') . '</legend>';

// Seitenauswahl
if($Values['stats_rss_id']) $validate_link = $baseurl.rex_getUrl($Values['stats_rss_id']);
$formElements = [];
$n = [];
$n['label'] = '<label for="REX_LINK_SELECT_3">' . $this->i18n('config_stats_rss_id') . '</label>';
$n['field'] = rex_var_link::getWidget(3, 'settings[stats_rss_id]', $this->getConfig('stats_rss_id'));
$n['note'] = htmlspecialchars_decode($this->i18n('stats_rss_id_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Text
$formElements = [];
$n = [];
$n['label'] = '<label for="stats_prefix">' . htmlspecialchars_decode($this->i18n('stats_prefix')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="stats_prefix" name="settings[stats_prefix]" value="' . $Values['stats_prefix'] . '" />';
$n['note'] = htmlspecialchars_decode($this->i18n('stats_prefix_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');


// Select
$formElements = [];
$n = [];
$n['label'] = '<label for="stats_rss_active">' . $this->i18n('stats_rss_active') . '</label>';
$select = new rex_select();
$select->setId('stats_rss_active');
$select->setAttribute('class', 'stats_rss_active');
$select->setName('settings[stats_rss_active]');
$select->addOption('Statistik-Prefixing deaktiviert','0');
$select->addOption('Statistik-Prefixing aktiviert','active');
$select->setSelected($this->getConfig('stats_rss_active'));
$n['field'] = $select->get();
$n['note'] = htmlspecialchars_decode($this->i18n('stats_rss_active_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');




$content .= '</fieldset>';


// ====== SERVER STATISTICS SETTINGS FIELDSET ======
$content .= '</fieldset><fieldset><legend>' . $this->i18n('server_statistics_settings') . '</legend>';

// Enable/Disable
$formElements = [];
$n = [];
$n['label'] = '<label for="stats_enabled">' . $this->i18n('stats_enabled') . '</label>';
$n['field'] = '<div class="checkbox"><label><input type="checkbox" id="stats_enabled" name="settings[stats_enabled]" value="1"' . ($Values['stats_enabled'] ? ' checked="checked"' : '') . ' /> ' . $this->i18n('stats_enabled_label') . '</label></div>';
$n['note'] = htmlspecialchars_decode($this->i18n('stats_enabled_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Provider Selection
$formElements = [];
$n = [];
$n['label'] = '<label for="stats_provider">' . $this->i18n('stats_provider') . '</label>';
$select = new rex_select();
$select->setId('stats_provider');
$select->setAttribute('class', 'stats_provider');
$select->setName('settings[stats_provider]');
$select->addOption('Webalizer (HTML Reports)', 'webalizer');
$select->addOption('AWStats (Text Reports)', 'awstats');
$select->setSelected($Values['stats_provider']);
$n['field'] = $select->get();
$n['note'] = htmlspecialchars_decode($this->i18n('stats_provider_help'));
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Path to Statistics Files
$formElements = [];
$n = [];
$n['label'] = '<label for="stats_path">' . htmlspecialchars_decode($this->i18n('stats_path')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="stats_path" name="settings[stats_path]" value="' . htmlspecialchars($Values['stats_path']) . '" placeholder="/usage/podcast_domain_de" />';

// Helper note mit möglichen Pfaden
$helperNote = htmlspecialchars_decode($this->i18n('stats_path_help')) . '<br><br>';
$helperNote .= '<strong>' . $this->i18n('stats_path_examples') . ':</strong><br>';
$helperNote .= '<div style="background-color: #f5f5f5; padding: 10px; margin-top: 8px; border-radius: 3px; font-family: monospace; font-size: 12px;">';
$helperNote .= '<strong>Webalizer (HTML):</strong><br>';
$helperNote .= '/usage/podcast_domain_de &nbsp;&nbsp;&nbsp;&nbsp;<em>(Relativ von Server-Root)</em><br>';
$helperNote .= '../../usage/podcast_domain_de &nbsp;&nbsp;&nbsp;&nbsp;<em>(Relativ von REDAXO-Root)</em><br>';
$helperNote .= '/home/user/public_html/usage/podcast_domain_de &nbsp;&nbsp;&nbsp;&nbsp;<em>(Absolut)</em><br>';
$helperNote .= '<br>';
$helperNote .= '<strong>AWStats (Text):</strong><br>';
$helperNote .= '/var/lib/awstats &nbsp;&nbsp;&nbsp;&nbsp;<em>(System-Standard auf Linux)</em><br>';
$helperNote .= '/home/user/awstats &nbsp;&nbsp;&nbsp;&nbsp;<em>(Nutzerspezifisch)</em><br>';
$helperNote .= '../../awstats &nbsp;&nbsp;&nbsp;&nbsp;<em>(Relativ von REDAXO-Root)</em><br>';
$helperNote .= '</div>';

$n['note'] = $helperNote;
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Info Box
$infoBox = '<div class="alert alert-info" style="margin-top: 15px;">';
$infoBox .= '<strong>' . $this->i18n('stats_info_title') . ':</strong><br>';
$infoBox .= '<ul style="margin: 8px 0; padding-left: 20px;">';
$infoBox .= '<li><strong>Webalizer:</strong> ' . $this->i18n('stats_info_webalizer') . '</li>';
$infoBox .= '<li><strong>AWStats:</strong> ' . $this->i18n('stats_info_awstats') . '</li>';
$infoBox .= '</ul>';
$infoBox .= '<p style="margin-top: 10px; font-size: 12px;">' . $this->i18n('stats_info_note') . '</p>';
$infoBox .= '</div>';

$formElements = [];
$n = [];
$n['field'] = $infoBox;
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

$content .= '</fieldset>';
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $this->i18n('save') . '">' . $this->i18n('save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

// Ausgabe Section
$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_config'), false);
$fragment->setVar('class', 'edit', false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');



$content = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="func" value="update" />
    ' . $content . '
</form>
';

echo $content;
