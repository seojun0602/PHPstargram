<?php
class Instagram
{
    protected $baseUrl = 'https://i.instagram.com/api/v1/feed/user/';
    protected $userAgent = 'Instagram 123.0.0.21.114 Android (18/4.3; 320dpi; 720x1280; Samsung; GT-I9300; m0; smdk4x12; en_US)';
    
    public function getUserFeed($username, $count = 12, $max_id = null)
    {
        $url = $this->baseUrl . $username . '/username/?count=' . $count;
        if ($max_id !== null) {
            $url .= '&max_id=' . urlencode($max_id);
        }

        $headers = [
            'User-Agent: ' . $this->userAgent,
            'Accept: */*',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function extractMediaUrls($feedData)
    {
        $mediaList = [];

        if (!isset($feedData['items'])) {
            return $mediaList;
        }

        foreach ($feedData['items'] as $item) {
            $media = [];

            if (isset($item['carousel_media'])) {
                foreach ($item['carousel_media'] as $carousel) {
                    $media[] = $this->getMediaFromItem($carousel);
                }
            } else {
                $media[] = $this->getMediaFromItem($item);
            }

            $mediaList[] = array_filter($media);
        }

        return $mediaList;
    }

    private function getMediaFromItem($item)
    {
        if (isset($item['image_versions2']['candidates'][0]['url'])) {
            return $item['image_versions2']['candidates'][0]['url'];
        } elseif (isset($item['video_versions'][0]['url'])) {
            return $item['video_versions'][0]['url'];
        }

        return null;
    }
    
    public function getAllMedia($username, $limit = 100)
{
    $media = [];
    $max_id = null;

    while (count($media) < $limit) {
        $data = $this->getUserFeed($username, 12, $max_id);
        if (!isset($data['items'])) break;

        $media = array_merge($media, $this->extractMediaUrls($data));
        $max_id = $data['next_max_id'] ?? null;

        if (!$max_id) break;
    }

    return array_slice($media, 0, $limit);
}

}
