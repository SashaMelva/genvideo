<?php

namespace App\Controller\Video;

use App\Controller\UserController;
use App\Helpers\CheckTokenExpiration;
use App\Models\ContentVideo;
use App\Models\ListImage;
use App\Models\ListMusic;
use App\Models\ListVideo;
use App\Models\TextVideo;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GetSettingVideo extends UserController
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function action(): ResponseInterface
    {
        $access_token = $this->request->getHeaderLine('Token');
        $contentId = $this->request->getAttribute('id');

        if (CheckTokenExpiration::action($this->container->get('jwt-secret'), $access_token)) {

            try {
                $logo = [];
                $slides = [];
                $videoStart = [];
                $videoBackground = [];
                $videoEnd = [];

                $content = ContentVideo::findAllDataByID($contentId);
                $images = ListImage::findAllByContentId($contentId);

                foreach ($images as $image) {
                    if ($image['type'] == 'logo') {
                        $logo[] = [
                            'id' => $image['id'],
                            'name' => $image['name'],
                            'extension' => '.' . explode('.', $image['file_name'])[1],
                            'type' => $image['type'],
                            'file' => file_exists(DIRECTORY_LOGO_IMG . $image['file_name']) === false ? 'file not found' : base64_encode(file_get_contents(DIRECTORY_LOGO_IMG . $image['file_name'])),
                            'file-name' => $image['file_name'],
                        ];
                    }

                    if ($image['type'] == 'slide') {
                        $slides[] = [
                            'id' => $image['id'],
                            'name' => $image['name'],
                            'extension' => '.' . explode('.', $image['file_name'])[1],
                            'type' => $image['type'],
                            'file' => file_exists(DIRECTORY_IMG . $image['file_name']) === false ? 'file not found' : base64_encode(file_get_contents(DIRECTORY_IMG . $image['file_name'])),
                            'file-name' => $image['file_name'],
                        ];
                    }
                }

                $sound = ListMusic::findAllByContentId($contentId);

                if (!empty($sound)) {
                    $sound = $sound[0];
                } else {
                    $sound = [
                        'id' => null,
                        'name' => null,
                        'type' => null,
                        'file_name' => null,
                    ];
                }

                $content['video'] = ListVideo::findAllByContentId($contentId);

                foreach ($content['video'] as $additionalVideo) {
                    if ($additionalVideo['type'] == 'content') {
                        $videoBackground[] = [
                            'id' => $additionalVideo['id'],
                            'file-name' => $additionalVideo['file_name'],
                            'type' => $additionalVideo['type'],
                            'name' => $additionalVideo['name'] . '.' . explode('.', $additionalVideo['file_name'])[1],
                        ];
                    }

                    if ($additionalVideo['type'] == 'start') {
                        $videoStart[] = [
                            'id' => $additionalVideo['id'],
                            'file-name' => $additionalVideo['file_name'],
                            'type' => $additionalVideo['type'],
                            'name' => $additionalVideo['name'] . '.' . explode('.', $additionalVideo['file_name'])[1],
                        ];
                    }

                    if ($additionalVideo['type'] == 'end') {
                        $videoEnd[] = [
                            'id' => $additionalVideo['id'],
                            'file-name' => $additionalVideo['file_name'],
                            'type' => $additionalVideo['type'],
                            'name' => $additionalVideo['name'] . '.' . explode('.', $additionalVideo['file_name'])[1],
                        ];
                    }
                }

                $textData = TextVideo::findById($content['text_id'])[0];
                $dataSetting = [
                    'content' => [
                        'content_id' => $content['content_id'],
                        'name' => $content['content_name'] . '.' . explode('.', $content['file_name'])[1],
                        'content_creator_id' => $content['content_creator_id'],
                        'type_background' => $content['type_background'],
                        'status_content_name' => $content['status_content_name'],
                        'delay_end_video' => $content['delay_end_video'],
                        'color_background_id' => $content['color_background_id'],
                        'color_background_name' => $content['color_background_name'],
                    ],
                    'preview' => [
                        'name' => is_null($content['preview_file_name']) ? null : $content['content_name'] . '.' . explode('.', $content['preview_file_name'])[1],
                        'text' => $content['preview_text'] ?? null,
                        'file_name' => $content['preview_file_name'] ?? null,
                        'file' => is_null($content['preview_file_name']) ? null : (file_exists(DIRECTORY_PREVIEW . $content['preview_file_name']) === false ? 'file not found' : base64_encode(file_get_contents(DIRECTORY_PREVIEW . $content['preview_file_name']))),
                    ],
                    'text' => [
                        'id' => $textData['id'],
                        'text' => $textData['text'],
                        'file-name-text-ass' => $textData['file_name_text'] . '.ass',
                        'file-name-text-srt' => $textData['file_name_text'] . '.srt',
                        'status-text' => $textData['status_text'],
                        'text-color-background' => $textData['text_color_background'],
                        'delay-between-offers' => $textData['delay_between_offers'],
                        'delay-between-paragraphs' => $textData['delay_between_paragraphs'],
                        'back-colour' => $textData['back_colour'],
                    ],
                    'img' => [
                        'logo' => $logo,
                        'slide-show' => $slides,
                    ],
                    'sound' => [
                        'id' => $sound['id'],
                        'name' => $sound['name'],
                        'type' => $sound['type'],
                        'file_name' => $sound['file_name'],
                        'extension' => '.' . explode('.', $sound['file_name'])[1],
                    ],
                    'speeckit' => [
                        'id' => $textData['id'],
                        'file-name-voice' => $textData['file_name_voice'],
                        'status-voice' => $textData['status_voice'],
                        'voice-speed' => $textData['voice_speed'],
                        'text' => $textData['text'],
                        'ampula-voice' => $content['ampula_voice'],
                        'dictionary-voice-name' => $content['dictionary_voice_name'],
                        'language' => $content['language']
                    ],
                    'additional-video' => [
                        'start-video' => $videoStart,
                        'content-video' => $videoBackground,
                        'end-video' => $videoEnd,
                    ]
                ];

                return $this->respondWithData($dataSetting);

            } catch (Throwable $e) {
                return $this->respondWithError($e->getCode(), $e->getMessage());
            }
        } else {
            return $this->respondWithError(215);
        }
    }
}