<?php

/**
 * Support functions for "statistics" about images and albums.
 *
 * Supports such statistics as "most popular", "latest", "top rated", etc.
 *
 * <b>CAUTION:</b> The way to get a specific album has changed. You now have to pass the foldername of an album instead the album title.
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard), gjr
 * @package zpcore\plugins\imagealbumstatistiscs
 */
$plugin_description = gettext("Functions that provide various statistics about images and albums in the gallery.");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard), gjr";
$plugin_category = gettext('Statistics');

require_once(dirname(dirname(__FILE__)) . '/template-functions.php');

/**
 * Returns a array of album objects of album statistic accordingly to $option
 *
 * @param int $number the number of albums to get
 * @param string $option
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder The name of an album to get only the statistc for its direct subalbums
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 * @return array
 */
function getAlbumStatistic($number = 5, $option = '', $albumfolder = '', $threshold = 0, $sortdirection = 'desc', $collection = false) {
  global $_zp_gallery, $_zp_db;
  if ($albumfolder) {
    $obj = AlbumBase::newAlbum($albumfolder);
    $albumWhere = ' WHERE parentid = ' . $obj->getID();
    if ($collection) {
      $albumWhere = '';
      $ids = getAllSubAlbumIDs($albumfolder);
      if (!empty($ids)) {
        foreach ($ids as $id) {
          $getids[] = $id['id'];
        }
        $getids = implode(', ', $getids);
        $albumWhere = ' WHERE id IN (' . $getids . ')';
      } else {
        $albumWhere = ' WHERE parentid = ' . $obj->getID();
      }
    }
  } else {
    $obj = $_zp_gallery;
    $albumWhere = '';
  }
  switch (strtolower($sortdirection)) {
    case 'desc':
    default:
      $sortdir = 'DESC';
      break;
    case 'asc':
      $sortdir = 'ASC';
      break;
  }
  if (($option == 'toprated' || $option == 'mostrated' || $option == 'popular') && $threshold > 0) {
    if (empty($albumWhere)) {
      $albumWhere = ' WHERE ';
    } else {
      $albumWhere .= ' AND ';
    }
    if (($option == 'toprated' || $option == 'mostrated') && $threshold > 0) {
      $albumWhere .= 'total_votes >= ' . $threshold;
    }
    if ($option == 'popular' && $threshold > 0) {
      $albumWhere .= 'hitcounter >= ' . $threshold;
    }
  }
  switch ($option) {
    case "popular":
      $sortorder = "hitcounter";
      break;
    default:
    case "latest":
      $sortorder = "id";
      break;
    case "latest-mtime":
      $sortorder = "mtime";
      break;
    case "latest-date":
      $sortorder = "date";
      break;
    case "latest-publishdate":
      $sortorder = "IFNULL(publishdate,date)";
      break;
    case "mostrated":
      $sortorder = "total_votes";
      break;
    case "toprated":
      $sortorder = "(total_value/total_votes) DESC, total_value";
      break;
    case "latestupdated":
      $sortorder = 'updateddate';
      break;
    case "random":
      $sortorder = "RAND()";
      break;
  }
  $albumArray = array();
  if ($obj->table == 'albums' && $obj->isDynamic()) {
    $albums = $obj->getAlbums(0, $sortorder, $sortdir);
    foreach ($albums as $album) {
      $album = AlbumBase::newAlbum($album);
      if ($album->checkAccess() && ($album->isPublic() || zp_loggedin(VIEW_UNPUBLISHED_RIGHTS))) {
        $albumArray[] = $album;
        if (count($albumArray) >= $number) { // got enough
          break;
        }
      }
    }
  } else {
    $result = $_zp_db->query("SELECT id, title, folder, thumb FROM " . $_zp_db->prefix('albums') . $albumWhere . " ORDER BY " . $sortorder . " " . $sortdir);
    while ($row = $_zp_db->fetchAssoc($result)) {
      $album = AlbumBase::newAlbum($row['folder'], true, true);
      if ($album->exists && $album->checkAccess() && ($album->isPublic() || zp_loggedin(VIEW_UNPUBLISHED_RIGHTS))) {
        //actually we only use "folder" but keep for backward compatibility in case someone uses those for now …
        $albumArray[] = $album;
        if (count($albumArray) >= $number) { // got enough
          break;
        }
      }
    }
    $_zp_db->freeResult($result);
  }
  return $albumArray;
}

/**
 * Prints album statistic according to $option as an unordered HTML list
 * A css id is attached by default named '$option_album'
 *
 * @param string $number the number of albums to get
 * @param string $option
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an album must have to be included in the list. (Default 0)
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printAlbumStatistic($number, $option, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $albumfolder = '', $firstimglink = false, $threshold = 0, $collection = false) {
	$albums = getAlbumStatistic($number, $option, $albumfolder, $threshold, $collection);
	echo "\n<div id=\"" . $option . "_album\">\n";
	echo "<ul>";
	foreach ($albums as $album) {
		printAlbumStatisticItem($album, $option, $showtitle, $showdate, $showdesc, $desclength, $showstatistic, $width, $height, $crop, $firstimglink);
	}
	echo "</ul></div>\n";
}

/**
 * A helper function that only prints a item of the loop within printAlbumStatistic()
 * Not for standalone use.
 *
 * @param array $album the array with album objects that getAlbumsStatistic() submitted
 * @param string $option
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param bool $showtitle if the album title should be shown
 * @param bool $showdate if the album date should be shown
 * @param bool $showdesc if the album description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic
 * 		"hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $firstimglink 'false' (default) if the album thumb link should lead to the album page, 'true' if to the first image of theh album if the album itself has images
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics to include all subalbum levels
 */
function printAlbumStatisticItem($album, $option, $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $firstimglink = false) {
	global $_zp_gallery, $_zp_db;
	$twidth = $width;
	$theight = $height;
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width))
			$width = 85;
		if (is_null($height))
			$height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	$tempalbum = $album;
	if ($firstimglink && $tempimage = $tempalbum->getImage(0)) {
		$albumpath = $tempimage->getLink();
	} else {
		$albumpath = $tempalbum->getLink();
	}
	echo "<li><a href=\"" . $albumpath . "\" title=\"" . html_encode($tempalbum->getTitle()) . "\">\n";
	$albumthumb = $tempalbum->getAlbumThumbImage();
	$attr = array(
					'src' => '',
					'width' => '',
					'height' => '',
					'alt' => html_encode($albumthumb->getTitle()),
					'title' => html_encode($albumthumb->getTitle()),
					'loading' => 'lazy'
			);
	switch ($crop) {
		case 0:
			$sizes = getSizeCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, $albumthumb, 'thumb');
			$attr['src'] = html_pathurlencode($albumthumb->getCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, TRUE));
			$attr['width'] = $sizes[0];
			$attr['height'] = $sizes[1];
			$attr_filtered = zp_apply_filter('custom_album_thumb_attr', $attr, $albumthumb);
			$attributes = generateAttributesFromArray($attr_filtered);
			$html = '<img' . $attributes . ' /></a>' . "\n";
			echo zp_apply_filter('custom_album_thumb_html', $html, $albumthumb);
			break;
		case 1;
			$sizes = getSizeCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, $albumthumb, 'thumb');
			$attr['src'] = html_pathurlencode($albumthumb->getCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, TRUE));
			$attr['width'] = $sizes[0];
			$attr['height'] = $sizes[1];
			$attr_filtered = zp_apply_filter('custom_album_thumb_attr', $attr, $albumthumb);
			$attributes = generateAttributesFromArray($attr_filtered);
			$html = '<img' . $attributes . '  /></a>' . "\n";
			echo zp_apply_filter('custom_album_thumb_html', $html, $albumthumb);
			break;
		case 2:
			$sizes = getSizeDefaultThumb($albumthumb);
			$attr['src'] = html_pathurlencode($albumthumb->getThumb());
			$attr['width'] = $sizes[0];
			$attr['height'] = $sizes[1];
			$attr_filtered = zp_apply_filter('standard_album_thumb_attr', $attr, $albumthumb);
			$attributes = generateAttributesFromArray($attr_filtered);
			$html = '<img' .$attributes .' /></a>' . "\n";
			echo zp_apply_filter('standard_album_thumb_html', $html, $albumthumb);
			break;
	}
	if ($showtitle) {
		echo "<h3><a href=\"" . $albumpath . "\" title=\"" . html_encode($tempalbum->getTitle()) . "\">\n";
		echo $tempalbum->getTitle() . "</a></h3>\n";
	}
	if ($showdate) {
		if ($option === "latestupdated") {
			$filechangedate = strtotime($tempalbum->getUpdatedDate());
			echo "<p>" . sprintf(gettext("Last update: %s"), zpFormattedDate(DATE_FORMAT, $filechangedate)) . "</p>";
			$latestimage = $_zp_db->querySingleRow("SELECT mtime FROM " . $_zp_db->prefix('images') . " WHERE albumid = " . $tempalbum->getID() . " AND `show` = 1 ORDER BY id DESC");
			if ($latestimage) {
				$count = $_zp_db->count('images', "WHERE albumid = " . $tempalbum->getID() . " AND mtime = " . $latestimage['mtime']);
				if ($count <= 1) {
					$image = gettext("image");
				} else {
					$image = gettext("images");
				}
				echo "<span>" . sprintf(gettext('%1$u new %2$s'), $count, $image) . "</span>";
			}
		} else {
			echo "<p>" . zpFormattedDate(DATE_FORMAT, strtotime($tempalbum->getDateTime())) . "</p>";
		}
	}
	if ($showstatistic === "rating" OR $showstatistic === "rating+hitcounter") {
		$votes = $tempalbum->get("total_votes");
		$value = $tempalbum->get("total_value");
		if ($votes != 0) {
			$rating = round($value / $votes, 1);
		}
		echo "<p>" . sprintf(gettext('Rating: %1$u (Votes: %2$u)'), $rating, $tempalbum->get("total_votes")) . "</p>";
	}
	if ($showstatistic === "hitcounter" OR $showstatistic === "rating+hitcounter") {
		$hitcounter = $tempalbum->getHitcounter();
		if (empty($hitcounter)) {
			$hitcounter = "0";
		}
		echo "<p>" . sprintf(gettext("Views: %u"), $hitcounter) . "</p>";
	}
	if ($showdesc) {
		echo shortenContent($tempalbum->getDesc(), $desclength, ' (...)');
	}
	echo "</li>";
}

/**
 * Returns a array of image objects of image statistic according to $option
 *
 * @param string $number the number of images to get
 * @param string $option "popular" for the most popular images,
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder foldername of an specific album
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 * @return array
 */
function getImageStatistic($number, $option, $albumfolder = '', $collection = false, $threshold = 0, $sortdirection = 'desc') {
  global $_zp_gallery, $_zp_db;
  if ($albumfolder) {
    $obj = AlbumBase::newAlbum($albumfolder);
    $albumWhere = ' AND albums.id = ' . $obj->getID();
    if ($collection) {
      $albumWhere = '';
      $ids = getAllSubAlbumIDs($albumfolder);
      if (!empty($ids)) {
        foreach ($ids as $id) {
          $getids[] = $id['id'];
        }
        $getids = implode(', ', $getids);
        $albumWhere = ' AND albums.id IN (' . $getids . ')';
      }
    } else {
      $albumWhere = ' AND albums.id = ' . $obj->getID();
    }
  } else {
    $obj = $_zp_gallery;
    $albumWhere = '';
  }
  if (($option == 'toprated' || $option == 'mostrated') && $threshold > 0) {
    $albumWhere .= 'AND images.total_votes >= ' . $threshold;
  }
  if ($option == 'popular' && $threshold > 0) {
    $albumWhere .= 'AND images.hitcounter >= ' . $threshold;
  }
  switch (strtolower($sortdirection)) {
    case 'desc':
    default:
      $sortdir = 'DESC';
      break;
    case 'asc':
      $sortdir = 'ASC';
      break;
  }
  switch ($option) {
    case "popular":
      $sortorder = "images.hitcounter";
      break;
    case "latest-date":
      $sortorder = "images.date";
      break;
    case "latest-mtime":
      $sortorder = "images.mtime";
      break;
    default:
    case "latest":
      $sortorder = "images.id";
      break;
    case "latest-publishdate":
      $sortorder = "IFNULL(images.publishdate,images.date)";
      break;
    case "mostrated":
      $sortorder = "images.total_votes";
      break;
    case "toprated":
      $sortorder = "(images.total_value/images.total_votes) DESC, images.total_value";
      break;
    case "random":
      $sortorder = "RAND()";
      break;
  }
  $imageArray = array();
  if (!empty($albumfolder) && $obj->isDynamic()) {
    $sorttype = str_replace('images.', '', $sortorder);
    $images = $obj->getImages(0, 0, $sorttype, $sortdir);
    foreach ($images as $image) {
      $image = Image::newImage($obj, $image);
      if ($image->exists && $image->checkAccess() && ($image->isPublic() || zp_loggedin(VIEW_UNPUBLISHED_RIGHTS))) {
        $imageArray[] = $image;
        if (count($imageArray) >= $number) { // got enough
          break;
        }
      }
    }
  } else {
    $result = $_zp_db->query("SELECT images.filename AS filename, albums.folder AS folder FROM " . $_zp_db->prefix('images') . " AS images, " . $_zp_db->prefix('albums') . " AS albums " . "WHERE (images.albumid = albums.id) " . $albumWhere . " ORDER BY " . $sortorder . " " . $sortdir);
    while ($row = $_zp_db->fetchAssoc($result)) {
      $image = Image::newImage(NULL, $row, true);
      if ($image->exists && $image->checkAccess() && ($image->isPublic() || zp_loggedin(VIEW_UNPUBLISHED_RIGHTS))) {
        $imageArray[] = $image;
        if (count($imageArray) >= $number) { // got enough
          break;
        }
      }
    }
    $_zp_db->freeResult($result);
  }
  return $imageArray;
}

/**
 * Prints image statistic according to $option as an unordered HTML list
 * A css id is attached by default named accordingly'$option'
 *
 * @param string $number the number of albums to get
 * @param string $option "popular" for the most popular images,
 * 		"popular" for the most popular albums,
 * 		"latest" for the latest uploaded by id (Discovery)
 * 		"latest-date" for the latest by date
 * 		"latest-mtime" for the latest by mtime
 *   	"latest-publishdate" for the latest by publishdate
 *    "mostrated" for the most voted,
 * 		"toprated" for the best voted
 * 		"latestupdated" for the latest updated
 * 		"random" for random order (yes, strictly no statistical order...)
 * @param string $albumfolder foldername of an specific album
 * @param bool $showtitle if the image title should be shown
 * @param bool $showdate if the image date should be shown
 * @param bool $showdesc if the image description should be shown
 * @param integer $desclength the length of the description to be shown
 * @param string $showstatistic "hitcounter" for showing the hitcounter (views),
 * 		"rating" for rating,
 * 		"rating+hitcounter" for both.
 * @param integer $width the width/cropwidth of the thumb if crop=true else $width is longest size. (Default 85px)
 * @param integer $height the height/cropheight of the thumb if crop=true else not used.  (Default 85px)
 * @param bool $crop 'true' (default) if the thumb should be cropped, 'false' if not
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * @param bool $fullimagelink 'false' (default) for the image page link , 'true' for the unprotected full image link (to use Colorbox for example)
 * @param integer $threshold the minimum number of ratings (for rating options) or hits (for popular option) an image must have to be included in the list. (Default 0)
 * @return string
 */
function printImageStatistic($number, $option, $albumfolder = '', $showtitle = false, $showdate = false, $showdesc = false, $desclength = 40, $showstatistic = '', $width = NULL, $height = NULL, $crop = NULL, $collection = false, $fullimagelink = false, $threshold = 0) {
	$images = getImageStatistic($number, $option, $albumfolder, $collection, $threshold);
	if (is_null($crop) && is_null($width) && is_null($height)) {
		$crop = 2;
	} else {
		if (is_null($width))
			$width = 85;
		if (is_null($height))
			$height = 85;
		if (is_null($crop)) {
			$crop = 1;
		} else {
			$crop = (int) $crop && true;
		}
	}
	echo "\n<div id=\"$option\">\n";
	echo "<ul>";
	foreach ($images as $image) {
		if ($fullimagelink) {
			$imagelink = $image->getFullImageURL();
		} else {
			$imagelink = $image->getLink();
		}
		echo '<li><a href="' . html_encode($imagelink) . '" title="' . html_encode($image->getTitle()) . "\">\n";
		$attr = array(
					'src' => '',
					'width' => '',
					'height' => '',
					'alt' => html_encode($image->getTitle()),
					'title' => html_encode($image->getTitle()),
					'loading' => 'lazy'
				);
		switch ($crop) {
			case 0:
				$sizes = getSizeCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, $image, 'thumb');
				$attr['src'] = html_pathurlencode($image->getCustomImage($width, NULL, NULL, NULL, NULL, NULL, NULL, TRUE));
				$attr['width'] = $sizes[0];
				$attr['height'] =  $sizes[1];
				$attr_filtered = zp_apply_filter('custom_image_attr', $attr, $image);
				$attributes = generateAttributesFromArray($attr_filtered);
				$html = '<img' . $attributes . '" /></a>' . "\n";
				echo zp_apply_filter('custom_image_html', $html, true, $image);
				break;
			case 1:
				$sizes = getSizeCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, $image, 'thumb');
				$attr['src'] = html_pathurlencode($image->getCustomImage(NULL, $width, $height, $width, $height, NULL, NULL, TRUE));
				$attr['width'] = $sizes[0];
				$attr['height'] =  $sizes[1];
				$attr_filtered = zp_apply_filter('custom_image_attr', $attr, $image);
				$attributes = generateAttributesFromArray($attr_filtered);
				$html = '<img' . $attributes . ' /></a>' . "\n";
				echo zp_apply_filter('custom_image_html', $html, true, $image);
				break;
			case 2:
				$sizes = getSizeDefaultThumb($image);
				$attr['src'] = html_pathurlencode($image->getThumb());
				$attr['width'] = $sizes[0];
				$attr['height'] =  $sizes[1];
				$attr_filtered = zp_apply_filter('standard_image_thumb_attr', $attr, $image);
				$attributes = generateAttributesFromArray($attr_filtered);
				$html = '<img' . $attributes . ' /></a>' . "\n";
				echo zp_apply_filter('standard_image_thumb_html', $html, $image);
				break;
		}
		if ($showtitle) {
			echo '<h3><a href="' . html_encode($image->getLink()) . '" title="' . html_encode($image->getTitle()) . "\">\n";
			echo $image->getTitle() . "</a></h3>\n";
		}
		if ($showdate) {
			echo "<p>" . zpFormattedDate(DATE_FORMAT, strtotime($image->getDateTime())) . "</p>";
		}
		if ($showstatistic === "rating" OR $showstatistic === "rating+hitcounter") {
			$votes = $image->get("total_votes");
			$value = $image->get("total_value");
			if ($votes != 0) {
				$rating = round($value / $votes, 1);
			}
			echo "<p>" . sprintf(gettext('Rating: %1$u (Votes: %2$u)'), $rating, $votes) . "</p>";
		}
		if ($showstatistic === "hitcounter" OR $showstatistic === "rating+hitcounter") {
			$hitcounter = $image->getHitcounter();
			if (empty($hitcounter)) {
				$hitcounter = "0";
			}
			echo "<p>" . sprintf(gettext("Views: %u"), $hitcounter) . "</p>";
		}
		if ($showdesc) {
			echo shortenContent($image->getDesc(), $desclength, ' (...)');
		}
		echo "</li>";
	}
	echo "</ul></div>\n";
}

/**
 * A little helper function that checks if an image or album is to be considered 'new' within the time range set in relation to getImageDate()/getAlbumDate()
 * Returns true or false.
 *
 * @param string $mode What to check "image" or "album".
 * @param integer $timerange The time range the item should be considered new. Default is 604800 (unix time seconds = ca. 7 days)
 * @return bool
 */
function checkIfNew($mode = "image", $timerange = 604800) {
	$currentdate = date("U");
	switch ($mode) {
		case "image":
			$itemdate = getImageDate("%s");
			break;
		case "album":
			$itemdate = getAlbumDate("%s");
			break;
	}
	$newcheck = $currentdate - $itemdate;
	if ($newcheck < $timerange) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Gets the number of all subalbums of all subalbum levels of either the current album or $albumobj
 *
 * @param object $albumobj Optional album object to check
 * @param string $pre Optional text you want to print before the number
 * @return bool
 */
function getNumAllSubalbums($albumobj, $pre = '') {
	global $_zp_gallery, $_zp_current_album;
	if (is_null($albumobj)) {
		$albumobj = $_zp_current_album;
	}
	$albums = $_zp_gallery->getAllAlbums($albumobj, LIST_RIGHTS, false);
	$count = count($albums);
	if ($count != 0) {
		return $pre . $count;
	} else {
		return false;
	}
}

/**
 * Returns an randomly selected image objecet from the gallery per day.
 * Returns null on failure
 * 
 * @since 1.5.8
 * 
 * @param string $albumfolder foldername of an specific album. If not set from all images in the gallery
 * @param bool $collection only if $albumfolder is set: true if you want to get statistics from this album and all of its subalbums
 * 
 * @return object|nill
 */
function getPictureOfTheDay($albumfolder = '', $collection = false) {
	global $_zp_gallery;
	$potd = getSerializedArray(getOption('picture_of_the_day'));
	if (date('Y-m-d', $potd['day']) == date('Y-m-d')) {
		$album = AlbumBase::newAlbum($potd['folder'], true, true);
		if ($album->exists) {
			$image = Image::newImage($album, $potd['filename'], true);
			if ($image->exists) {
				return $image;
			}
		}
	}
	$randomimage = getImageStatistic(1, 'random', $albumfolder, $collection);
	if ($randomimage) {
		$potd = array('day' => time(), 'folder' => $randomimage[0]->getAlbumName(), 'filename' => $randomimage[0]->getName());
		setThemeOption('picture_of_the_day', serialize($potd), NULL, $_zp_gallery->getCurrentTheme());
		return $randomimage[0];
	}
	return NULL;
}
