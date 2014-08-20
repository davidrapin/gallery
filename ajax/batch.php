<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAppEnabled('gallery');

$square = isset($_GET['square']) ? (bool)$_GET['square'] : false;
$scale = isset($_GET['scale']) ? $_GET['scale'] : 1;
$owner = $_GET['owner'];
$images = explode(';', $_GET['image']);
$linkItem = \OCP\Share::getShareByToken($owner);

if (is_array($linkItem) && isset($linkItem['uid_owner'])) {
	// seems to be a valid share
	$rootLinkItem = \OCP\Share::resolveReShare($linkItem);
	$user = $rootLinkItem['uid_owner'];

	// Setup filesystem
	OCP\JSON::checkUserExists($user);
	OC_Util::tearDownFS();
	OC_Util::setupFS($user);

	$fullPath = \OC\Files\Filesystem::getPath($linkItem['file_source']);
	$images = array_map(function ($image) use ($fullPath) {
		return $fullPath . '/' . $image;
	}, $images);
} else {
	OCP\JSON::checkLoggedIn();
	$user = OCP\User::getUser();
}

session_write_close();
$eventSource = new OC_EventSource();

foreach ($images as $image) {
	$height = 200 * $scale;
	if ($square) {
		$width = 200 * $scale;
	} else {
		$width = 400 * $scale;
	}

	$preview = new \OC\Preview($user, 'files', '/' . $image, $width, $height);
	$preview->setKeepAspect(!$square);

	$eventSource->send('preview', array(
		'image' => $image,
		'preview' => (string)$preview->getPreview()
	));
}
$eventSource->close();