<?php

header('Content-Type: application/json');
if (!isset($global['systemRootPath'])) {
    require_once '../../videos/configuration.php';
}

$ShortsObj = AVideoPlugin::getDataObjectIfEnabled("Shorts");
$videos = ['data'=>[], 'draw'=>0, 'recordsTotal'=>0, 'recordsFiltered'=>0];
if(!empty($ShortsObj)){
    $sort = @$_POST['sort'];
    $rowCount = @$_REQUEST['rowCount'];

    $videos['draw'] = getCurrentPage();

    $_POST['sort']['created'] = 'DESC';
    $_REQUEST['rowCount'] = 12;

    $videos['recordsTotal'] = Video::getTotalVideos("viewable", false, false, false, true, false, "video", $ShortsObj->shortMaxDurationInSeconds);
    //getAllVideos($status = "viewable", $showOnlyLoggedUserVideos = false, $ignoreGroup = false, $videosArrayId = [], $getStatistcs = false, $showUnlisted = false, $activeUsersOnly = true, $suggestedOnly = false, $is_serie = null, $type = '', $max_duration_in_seconds=0) {
    $videos['data'] = Video::getAllVideos("viewable", false, false, [], false, false, true, false, null, "video", $ShortsObj->shortMaxDurationInSeconds);
    foreach ($videos['data'] as $key => $video) {
        $images = object_to_array(Video::getImageFromFilename($video['filename'], $video['type']));
        $videos['data'][$key]['images'] = $images;
    }
    $videos['recordsFiltered'] = count($videos['data']);

    $_POST['sort'] = $sort;
    $_REQUEST['rowCount'] = $rowCount;
    
}
echo json_encode($videos);
