<?php

use FriendsOfRedaxo\Podcastmanager\Statistics\StatisticsManager;

$content = '';
$buttons = '';

// Get current base URL
if (!rex_addon::get('yrewrite')->isAvailable()) {
    $baseurl = rex::getServer();
} else {
    $baseurl = rex_yrewrite::getCurrentDomain()->getUrl();
}

$func = rex_request('func', 'string');
$action = rex_request('action', 'string');

// Get addon configuration
$statsPath = rex_addon::get('podcastmanager')->getConfig('stats_path', '');
$statsProvider = rex_addon::get('podcastmanager')->getConfig('stats_provider', 'webalizer');
$statsEnabled = rex_addon::get('podcastmanager')->getConfig('stats_enabled', false);

// Handle configuration
if ($func === 'config' && rex_perm::hasAll('admin[]')) {
    if ($action === 'save') {
        $newPath = rex_request('stats_path', 'string', '');
        $newProvider = rex_request('stats_provider', 'string', 'webalizer');
        $newEnabled = (bool)rex_request('stats_enabled', 'string', false);
        
        rex_addon::get('podcastmanager')->setConfig('stats_path', $newPath);
        rex_addon::get('podcastmanager')->setConfig('stats_provider', $newProvider);
        rex_addon::get('podcastmanager')->setConfig('stats_enabled', $newEnabled);
        
        $content .= rex_view::success('Konfiguration gespeichert');
    }
    
    // Configuration form
    $content .= '<div class="panel panel-default">';
    $content .= '<div class="panel-heading"><h3 class="panel-title">Statistik-Konfiguration</h3></div>';
    $content .= '<div class="panel-body">';
    $content .= '<form method="post" class="form-horizontal">';
    $content .= rex_csrf_token::factory('podcastmanager-stats')->getHiddenField();
    $content .= rex_view::hidden('action', 'save');
    
    // Stats Path
    $content .= '<div class="form-group">';
    $content .= '<label class="col-sm-2 control-label">Pfad zu Statistik-Dateien</label>';
    $content .= '<div class="col-sm-10">';
    $content .= '<input type="text" class="form-control" name="stats_path" value="' . htmlspecialchars($statsPath) . '" placeholder="/usage/podcast_domain_de">';
    $content .= '<p class="help-block">Relativer Pfad zum Server-Root oder absoluter Pfad zu Webalizer/AWStats Dateien</p>';
    $content .= '</div>';
    $content .= '</div>';
    
    // Provider selection
    $content .= '<div class="form-group">';
    $content .= '<label class="col-sm-2 control-label">Statistik-Tool</label>';
    $content .= '<div class="col-sm-10">';
    $content .= '<select class="form-control" name="stats_provider">';
    foreach (StatisticsManager::getAvailableProviders() as $type => $name) {
        $selected = $statsProvider === $type ? 'selected' : '';
        $content .= '<option value="' . $type . '" ' . $selected . '>' . $name . '</option>';
    }
    $content .= '</select>';
    $content .= '<p class="help-block">Wähle das auf deinem Server verfügbare Statistik-Tool</p>';
    $content .= '</div>';
    $content .= '</div>';
    
    // Enable/disable
    $content .= '<div class="form-group">';
    $content .= '<label class="col-sm-2 control-label">Aktivieren</label>';
    $content .= '<div class="col-sm-10">';
    $content .= '<label>';
    $checked = $statsEnabled ? 'checked' : '';
    $content .= '<input type="checkbox" name="stats_enabled" value="1" ' . $checked . '> Statistiken anzeigen';
    $content .= '</label>';
    $content .= '</div>';
    $content .= '</div>';
    
    // Submit
    $content .= '<div class="form-group">';
    $content .= '<div class="col-sm-10 col-sm-offset-2">';
    $content .= '<button type="submit" class="btn btn-primary">Speichern</button>';
    $content .= '</div>';
    $content .= '</div>';
    
    $content .= '</form>';
    $content .= '</div>';
    $content .= '</div>';
    
    // Provider info
    $content .= '<div class="panel panel-info">';
    $content .= '<div class="panel-heading"><h3 class="panel-title">Verfügbare Statistik-Tools</h3></div>';
    $content .= '<div class="panel-body">';
    foreach (StatisticsManager::getAvailableProviders() as $type => $name) {
        $info = StatisticsManager::getProviderInfo($type);
        $content .= '<h4>' . $name . '</h4>';
        $content .= '<p>' . $info['description'] . '</p>';
        $content .= '<strong>Dateiformat:</strong> ' . $info['file_format'] . '<br>';
        $content .= '<strong>Anforderungen:</strong> ' . $info['requirements'] . '<br>';
        $content .= '</div>';
        $content .= '<hr>';
    }
    $content .= '</div>';
    $content .= '</div>';
    
} else if ($statsEnabled && $statsPath) {
    // Display statistics
    try {
        $domain = rex_yrewrite::getCurrentDomain()->getName();
        $provider = StatisticsManager::createProvider($statsProvider, $statsPath, $domain);
        
        if ($provider->isAvailable()) {
            $months = $provider->getAvailableMonths();
            
            $content .= '<div class="alert alert-info">';
            $content .= 'Provider: <strong>' . $provider->getProviderName() . '</strong> | ';
            $content .= 'Verfügbare Monate: <strong>' . count($months) . '</strong>';
            $content .= '</div>';
            
            // Display latest statistics
            if (!empty($months)) {
                $latestMonth = $months[0];
                $month = substr($latestMonth, 5, 2);
                $year = substr($latestMonth, 0, 4);
                
                try {
                    $stats = $provider->getStatistics($month, $year);
                    $data = $stats->getAll();
                    
                    $content .= '<div class="panel panel-default">';
                    $content .= '<div class="panel-heading"><h3 class="panel-title">Statistiken ' . $latestMonth . '</h3></div>';
                    $content .= '<div class="panel-body">';
                    $content .= '<table class="table table-striped">';
                    $content .= '<tr><td>Besuche</td><td>' . number_format($stats->getVisits(), 0, ',', '.') . '</td></tr>';
                    $content .= '<tr><td>Hits</td><td>' . number_format($stats->getHits(), 0, ',', '.') . '</td></tr>';
                    $content .= '<tr><td>Seiten</td><td>' . number_format($stats->getPages(), 0, ',', '.') . '</td></tr>';
                    $content .= '<tr><td>Bandbreite</td><td>' . $data['raw_data']['bandwidth'] . ' (' . number_format($stats->getBandwidth(), 0, ',', '.') . ' Bytes)</td></tr>';
                    $content .= '<tr><td>Bots</td><td>' . number_format($stats->getBots(), 0, ',', '.') . '</td></tr>';
                    $content .= '<tr><td>Fehlerhafte Anfragen</td><td>' . number_format($stats->getFailedRequests(), 0, ',', '.') . '</td></tr>';
                    $content .= '</table>';
                    $content .= '</div>';
                    $content .= '</div>';
                    
                } catch (\Exception $e) {
                    $content .= rex_view::error('Fehler beim Laden der Statistiken: ' . $e->getMessage());
                }
            }
        } else {
            $content .= rex_view::warning('Statistik-Pfad nicht verfügbar: ' . $statsPath);
        }
    } catch (\Exception $e) {
        $content .= rex_view::error('Statistik-Provider Fehler: ' . $e->getMessage());
    }
} else if (!$statsEnabled) {
    $content .= rex_view::info('Statistiken sind nicht aktiviert. <a href="' . rex_url::currentBackendPage(['func' => 'config']) . '">Zur Konfiguration</a>');
} else {
    $content .= rex_view::warning('Statistiken sind nicht konfiguriert. <a href="' . rex_url::currentBackendPage(['func' => 'config']) . '">Zur Konfiguration</a>');
}