<?php
$target_page = rex_request( 'page', 'string' );

if( $target_page == 'yform/manager/data_edit' )
{
    $table_name = rex_request( 'table_name', 'string' );
    $envelope = '';
}
elseif( isset( $this->getProperty('page')['subpages'][rex_be_controller::getCurrentPagePart(2)] ) )
{
    $properties = $this->getProperty('page')['subpages'][rex_be_controller::getCurrentPagePart(2)];
    if( $sub=rex_be_controller::getCurrentPagePart(3) ) $properties = $properties['subpages'][$sub];
    $table_name = isset( $properties['yformTable'] ) ? $properties['yformTable'] : '';
    $envelope = isset( $properties['yformClass'] ) ? $properties['yformClass'] : '';
    $hideTitle = isset( $properties['yformTitle'] ) && $properties['yformTitle'] == false ?  "<style>.$envelope header.rex-page-header{display:none;}</style>": '';
}
else
{
    $table_name = '';
}

$table = rex_yform_manager_table::get($table_name);

if ($table && rex::getUser() && (rex::getUser()->isAdmin() || rex::getUser()->getComplexPerm('yform_manager_table')->hasPerm($table->getTableName()))) {
    try {
        $page = new rex_yform_manager();
        $page->setTable($table);
        $page->setLinkVars(['page' => $target_page, 'table_name' => $table->getTableName()]);
        if( $envelope ) echo "<div class=\"$envelope\">$hideTitle";
        echo $page->getDataPage();
        if( $class ) echo '</div>';
    } catch (Exception $e) {
        $message = nl2br($e->getMessage()."\n".$e->getTraceAsString());
        echo rex_view::warning($message);
    }
} else {
    if (!$table) {
        echo rex_view::warning(rex_i18n::msg('yform_table_not_found'));
    }
}