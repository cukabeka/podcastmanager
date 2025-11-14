<?php
/**
 * YForm Value Class: Audio Preview
 * 
 * Displays an audio player preview in the YForm backend
 * Supports vidstack addon if available, falls back to HTML5 audio
 */
class rex_yform_value_audio_preview extends rex_yform_value_abstract
{
    public function enterObject()
    {
        // This field is display-only, doesn't process input
        $this->setValue($this->getValue());
    }

    public function getDescription(): string
    {
        return 'audio_preview|name|label|[audio_field_name]|[show_duration]|[show_filesize]';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'audio_preview',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'audio_field' => ['type' => 'text', 'label' => 'Audio-Feld Name', 'default' => 'audiofiles'],
                'show_duration' => ['type' => 'boolean', 'label' => 'Laufzeit anzeigen', 'default' => true],
                'show_filesize' => ['type' => 'boolean', 'label' => 'Dateigröße anzeigen', 'default' => true],
            ],
        ];
    }

    public function getView()
    {
        $audioFieldName = $this->getElement('audio_field') ?: 'audiofiles';
        $showDuration = (bool)$this->getElement('show_duration');
        $showFilesize = (bool)$this->getElement('show_filesize');
        
        // Get audio file from another field
        $audioFile = $this->params['value_pool']['sql'][$audioFieldName] ?? '';
        
        if (empty($audioFile)) {
            return $this->parse('value.text.tpl.php', ['value' => '<p class="help-block">Keine Audio-Datei vorhanden</p>']);
        }
        
        // Check if file exists
        $media = rex_media::get($audioFile);
        if (!$media) {
            return $this->parse('value.text.tpl.php', ['value' => '<p class="help-block text-danger">Audio-Datei nicht gefunden: ' . htmlspecialchars($audioFile) . '</p>']);
        }
        
        // Build audio player HTML
        $html = '<div class="audio-preview-wrapper">';
        $html .= '<label class="control-label">' . htmlspecialchars($this->getElement('label')) . '</label>';
        $html .= '<div class="audio-preview-player">';
        
        // Try vidstack first
        if (rex_addon::exists('vidstack') && rex_addon::get('vidstack')->isAvailable()) {
            $html .= $this->renderVidstackPlayer($audioFile, $media);
        } else {
            $html .= $this->renderHtml5Player($audioFile, $media);
        }
        
        $html .= '</div>';
        
        // Show file information
        if ($showDuration || $showFilesize) {
            $html .= '<div class="audio-preview-info">';
            
            if ($showFilesize) {
                $filesize = rex_formatter::bytes($media->getSize());
                $html .= '<span class="audio-info-item"><i class="fa fa-file"></i> ' . $filesize . '</span>';
            }
            
            if ($showDuration) {
                $duration = $this->getAudioDuration($media);
                if ($duration) {
                    $html .= '<span class="audio-info-item"><i class="fa fa-clock-o"></i> ' . $duration . '</span>';
                }
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Add CSS
        $html .= '<style>
            .audio-preview-wrapper { margin-bottom: 15px; }
            .audio-preview-player { 
                background: #f5f5f5; 
                padding: 15px; 
                border-radius: 4px;
                border: 1px solid #ddd;
            }
            .audio-preview-info { 
                margin-top: 10px; 
                padding: 5px;
                font-size: 12px;
                color: #666;
            }
            .audio-info-item { 
                margin-right: 15px; 
                display: inline-block;
            }
            .audio-info-item i { margin-right: 5px; }
            .podcast-audio-player { 
                width: 100%; 
                max-width: 600px;
            }
        </style>';
        
        return $this->parse('value.text.tpl.php', ['value' => $html]);
    }
    
    /**
     * Render audio player using vidstack addon
     */
    private function renderVidstackPlayer($audioFile, $media)
    {
        try {
            $video = new \FriendsOfRedaxo\VidStack\Video($audioFile, $media->getTitle());
            
            $video->setAttributes([
                'controls' => true,
                'preload' => 'metadata',
                'class' => 'podcast-audio-player'
            ]);
            
            return $video->generate();
        } catch (Exception $e) {
            return $this->renderHtml5Player($audioFile, $media);
        }
    }
    
    /**
     * Render HTML5 audio player
     */
    private function renderHtml5Player($audioFile, $media)
    {
        $audioUrl = rex_url::media($audioFile);
        
        $html = '<audio controls preload="metadata" class="podcast-audio-player">';
        $html .= '<source src="' . htmlspecialchars($audioUrl) . '" type="' . htmlspecialchars($media->getType()) . '">';
        $html .= 'Your browser does not support the audio element.';
        $html .= '</audio>';
        
        return $html;
    }
    
    /**
     * Get audio duration from ID3 tags
     */
    private function getAudioDuration($media)
    {
        if (!class_exists('getID3')) {
            return null;
        }
        
        try {
            $getID3 = new getID3;
            $fileInfo = $getID3->analyze(rex_path::media($media->getFileName()));
            
            if (isset($fileInfo['playtime_seconds'])) {
                $seconds = (int)$fileInfo['playtime_seconds'];
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $secs = $seconds % 60;
                
                if ($hours > 0) {
                    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
                } else {
                    return sprintf('%02d:%02d', $minutes, $secs);
                }
            }
        } catch (Exception $e) {
            // Silently fail
        }
        
        return null;
    }
}
