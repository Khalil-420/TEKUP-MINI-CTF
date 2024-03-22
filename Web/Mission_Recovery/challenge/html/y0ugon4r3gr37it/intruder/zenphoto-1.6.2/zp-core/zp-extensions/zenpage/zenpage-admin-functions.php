<?php
/**
 * zenpage admin functions
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package zpcore\plugins\zenpage\admin
 */
global $_zp_zenpage, $_zp_current_zenpage_news, $_zp_current_zenpage_page, $_zp_current_category;
Zenpage::expiry();


/**
 * Retrieves posted expiry date and checks it against the current date/time
 * Returns the posted date if it is in the future
 * Returns NULL if the date is past
 *
 * @return string
 */
function getExpiryDatePost() {
	$expiredate = sanitize($_POST['expiredate']);
	if ($expiredate > date(date('Y-m-d H:i:s')))
		return $expiredate;
	return NULL;
}

/**
 * processes the taglist save
 *
 * @param object $object the object on which the save happened
 */
function processTags($object) {
	$tagsprefix = 'tags_';
	$tags = array();
	$l = strlen($tagsprefix);
	foreach ($_POST as $key => $value) {
		$key = postIndexDecode($key);
		if (substr($key, 0, $l) == $tagsprefix) {
			if ($value) {
				$tags[] = substr($key, $l);
			}
		}
	}
	$tags = array_unique($tags);
	$object->setTags(sanitize($tags, 3));
}

/* * ************************
  /* page functions
 * ************************* */

/**
 * Updates or adds a page and returns the object of that page
 *
 * @param array $reports report display
 * @param bool $newpage true if it is a new page
 *
 * @return object
 */
function updatePage(&$reports, $newpage = false) {
	global $_zp_zenpage, $_zp_current_admin_obj, $_zp_db;
	$title = process_language_string_save("title", 2);
	$author = sanitize($_POST['author']);
	$content = updateImageProcessorLink(process_language_string_save("content", EDITOR_SANITIZE_LEVEL));
	$extracontent = updateImageProcessorLink(process_language_string_save("extracontent", EDITOR_SANITIZE_LEVEL));
	$custom = process_language_string_save("custom_data", 1);
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$expiredate = getExpiryDatePost();
	$commentson = getcheckboxState('commentson');
	$permalink = getcheckboxState('permalink');
	if (zp_loggedin(CODEBLOCK_RIGHTS)) {
		$codeblock = processCodeblockSave(0);
	}
	$locked = getcheckboxState('locked');
	if ($newpage) {
		$titlelink = createTitlelink($title, $date);
		if(getOption('zenpage_titlelinkdate_pages')) {
			$titlelink = addDateToTitlelink($titlelink);
		}
		$duplicate = checkTitlelinkDuplicate($titlelink, 'page');
		if ($duplicate) {
			//already exists
			$titlelink = addDateToTitlelink($titlelink);
			$reports[] = "<p class='warningbox fade-message'>" . gettext('Duplicate page title') . '</p>';
		}
		$oldtitlelink = $titlelink;
	} else {
		$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old']);
	}
	if (getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'], 3);
		if (empty($titlelink)) {
			$titlelink = createTitlelink($title, $date);
		}
	} else {
		if (!$permalink) { //	allow the link to change
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}
	$id = sanitize($_POST['id']);
	$rslt = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$rslt = $_zp_db->query('UPDATE ' . $_zp_db->prefix('pages') . ' SET `titlelink`=' . $_zp_db->quote($titlelink) . ' WHERE `id`=' . $id, false);
		if (!$rslt) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		} else {
			SearchEngine::clearSearchCache();
		}
	}
	// update page
	$page = new ZenpagePage($titlelink, true);

	$notice = processCredentials($page);
	$page->setTitle($title);
	$page->setContent($content);
	$page->setExtracontent($extracontent);
	$page->setCustomData(zp_apply_filter('save_page_custom_data', $custom, $page));
	$page->setPublished($show);
	$page->setDateTime($date);
	$page->setLastChange($date);
	$page->setCommentsAllowed($commentson);
	if (zp_loggedin(CODEBLOCK_RIGHTS)) {
		$page->setCodeblock($codeblock);
	}
	$page->setAuthor($author);
	$page->setPermalink($permalink);
	$page->setLocked($locked);
	$page->setExpiredate($expiredate);
	if (getcheckboxState('resethitcounter')) {
		$page->set('hitcounter', 0);
	}
	if (getcheckboxState('reset_rating')) {
		$page->set('total_value', 0);
		$page->set('total_votes', 0);
		$page->set('used_ips', 0);
	}
	processTags($page);
	if ($newpage) {
		$page->setDefaultSortorder();
		$msg = zp_apply_filter('new_page', '', $page);
		if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Page <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else if ($notice == '?mismatch=user') {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Page user') . '</p>';
		} else if ($notice) {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Page <em>%s</em> added"), $titlelink) . '</p>';
		}
	} else {
		$page->setLastchangeUser($_zp_current_admin_obj->getUser());
		$msg = zp_apply_filter('update_page', '', $page, $oldtitlelink);
		if (!$rslt) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("A page with the title/titlelink <em>%s</em> already exists!"), $titlelink) . '</p>';
		} else if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Page <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else if ($notice == '?mismatch=user') {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Page user') . '</p>';
		} else if ($notice) {
			echo "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Page <em>%s</em> updated"), $titlelink) . '</p>';
		}
	}
	$checkupdates = true;
	if ($newpage) { 
		$checkupdates = false;
	}
	$page->save($checkupdates);
	if ($msg) {
		$reports[] = $msg;
	}
	return $page;
}

/**
 * Deletes a page (and also if existing its subpages) from the database
 *
 */
function deletePage($titlelink) {
	if (is_object($titlelink)) {
		$obj = $titlelink;
	} else {
		$obj = new ZenpagePage($titlelink);
	}
	$result = $obj->remove();
	if ($result) {
		if (is_object($titlelink)) {
			redirectURL(FULLWEBPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/zenpage/admin-pages.php?deleted');
		}
		SearchEngine::clearSearchCache();
		return "<p class='messagebox fade-message'>" . gettext("Page successfully deleted!") . "</p>";
	}
	return "<p class='errorbox fade-message'>" . gettext("Page delete failed!") . "</p>";
}

/**
 * Prints the table part of a single page item for the sortable pages list
 *
 * @param object $page The array containing the single page
 * @param bool $flag set to true to flag the element as having a problem with nesting level
 */
function printPagesListTable($page, $flag) {
	if ($flag) {
		$img = '../../images/drag_handle_flag.png';
	} else {
		$img = '../../images/drag_handle.png';
	}
	?>
	<div class='page-list_row'>
		<div class="page-list_title">
			<?php
			if (checkIfLockedPage($page)) {
				echo "<a href='admin-edit.php?page&amp;titlelink=" . urlencode($page->getName()) . "'> ";
				checkForEmptyTitle($page->getTitle(), "page");
				echo "</a>" . checkHitcounterDisplay($page->getHitcounter());
			} else {
				checkForEmptyTitle($page->getTitle(), "page");
				checkHitcounterDisplay($page->isPublished());
			}
			?>
		</div>
		<div class="page-list_extra">
			<span>
				<?php echo html_encode($page->getAuthor()); ?>
			</span>
		</div>
		<div class="page-list_extra">
			<?php printPublished($page); ?>
		</div>
		<div class="page-list_extra">
			<?php printExpired($page); ?>
		</div>
		<div class="page-list_iconwrapper">
			<div class="page-list_icon">
				<?php printProtectedIcon($page); ?>
			</div>

			<?php if (checkIfLockedPage($page)) { ?>
				<div class="page-list_icon">
					<?php printPublishIconLink($page, "page"); ?>
				</div>
				<?php if(extensionEnabled('comment_form')) { ?>
					<div class="page-list_icon">
						<?php
							if ($page->getCommentsAllowed()) {
								?>
								<a href="?commentson=0&amp;titlelink=<?php echo html_encode($page->getName()); ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo gettext('Disable comments'); ?>">
									<img src="../../images/comments-on.png" alt="" title="<?php echo gettext("Comments on"); ?>" style="border: 0px;"/>
								</a>
								<?php
							} else {
								?>
								<a href="?commentson=1&amp;titlelink=<?php echo html_encode($page->getName()); ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>" title="<?php echo gettext('Enable comments'); ?>">
									<img src="../../images/comments-off.png" alt="" title="<?php echo gettext("Comments off"); ?>" style="border: 0px;"/>
								</a>
								<?php
							}
						?>
					</div>
				<?php } ?>
			<?php } else { ?>
				<div class="page-list_icon">
					<img src="../../images/icon_inactive.png" alt="" title="<?php gettext('locked'); ?>" />
				</div>
				<div class="page-list_icon">
					<img src="../../images/icon_inactive.png" alt="" title="<?php gettext('locked'); ?>" />
				</div>
			<?php } ?>

			<div class="page-list_icon">
				<a href="../../../index.php?p=pages&amp;title=<?php echo js_encode($page->getName()); ?>" title="<?php echo gettext("View page"); ?>">
					<img src="images/view.png" alt="" title="<?php echo gettext("view"); ?>" />
				</a>
			</div>

			<?php
			if (checkIfLockedPage($page)) {
				if (extensionEnabled('hitcounter')) {
					?>
					<div class="page-list_icon">
						<a href="?hitcounter=1&amp;titlelink=<?php echo html_encode($page->getName()); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('hitcounter') ?>" title="<?php echo gettext("Reset hitcounter"); ?>">
							<img src="../../images/reset.png" alt="" title="<?php echo gettext("Reset hitcounter"); ?>" /></a>
					</div>
					<?php
				}
				?>
				<div class="page-list_icon">
					<a href="javascript:confirmDelete('admin-pages.php?delete=<?php echo $page->getName(); ?>&amp;add&amp;XSRFToken=<?php echo getXSRFToken('delete') ?>',deletePage)" title="<?php echo gettext("Delete page"); ?>">
						<img src="../../images/fail.png" alt="" title="<?php echo gettext("delete"); ?>" /></a>
				</div>
				<div class="page-list_icon">
					<input class="checkbox" type="checkbox" name="ids[]" value="<?php echo $page->getName(); ?>" onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
				</div>
			<?php } else { ?>
				<div class="page-list_icon">
					<img src="../../images/icon_inactive.png" alt="" title="<?php gettext('locked'); ?>" />
				</div>
				<div class="page-list_icon">
					<img src="../../images/icon_inactive.png" alt="" title="<?php gettext('locked'); ?>" />
				</div>
				<div class="page-list_icon">
					<img src="../../images/icon_inactive.png" alt="" title="<?php gettext('locked'); ?>" />
				</div>
			<?php } ?>
		</div><!--  icon wrapper end -->
	</div>
	<?php
}

/* * ************************
  /* news article functions
 * ************************* */

/**
 * Updates or adds a news article and returns the object of that article
 *
 * @param array $reports display
 * @param bool $newarticle true if a new article
 *
 * @return object
 */
function updateArticle(&$reports, $newarticle = false) {
	global $_zp_current_admin_obj, $_zp_db;
	$date = date('Y-m-d_H-i-s');
	$title = process_language_string_save("title", 2);
	$author = sanitize($_POST['author']);
	$content = updateImageProcessorLink(process_language_string_save("content", EDITOR_SANITIZE_LEVEL));
	$extracontent = updateImageProcessorLink(process_language_string_save("extracontent", EDITOR_SANITIZE_LEVEL));
	$custom = process_language_string_save("custom_data", 1);
	$show = getcheckboxState('show');
	$date = sanitize($_POST['date']);
	$expiredate = getExpiryDatePost();
	$permalink = getcheckboxState('permalink');
	$commentson = getcheckboxState('commentson');
	if (zp_loggedin(CODEBLOCK_RIGHTS)) {
		$codeblock = processCodeblockSave(0);
	}
	$locked = getcheckboxState('locked');
	if ($newarticle) {
		$titlelink = createTitlelink($title, $date);
		if(getOption('zenpage_titlelinkdate_articles')) {
			$titlelink = addDateToTitlelink($titlelink);
		}
		$duplicate = checkTitlelinkDuplicate($titlelink, 'article');
		if ($duplicate) {
			//already exists
			$titlelink = addDateToTitlelink($titlelink);
			$reports[] = "<p class='warningbox fade-message'>" . gettext('Duplicate article title') . '</p>';
		}
		$oldtitlelink = $titlelink;
		$id = 0;
	} else {
		$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old'], 3);
		$id = sanitize($_POST['id']);
	}

	if (getcheckboxState('edittitlelink')) {
		$titlelink = sanitize($_POST['titlelink'], 3);
		if (empty($titlelink)) {
			$titlelink = createTitlelink($title, $date);
		}
	} else {
		if (!$permalink) { //	allow the title link to change.
			$link = seoFriendly(get_language_string($title));
			if (!empty($link)) {
				$titlelink = $link;
			}
		}
	}

	$rslt = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$rslt = $_zp_db->query('UPDATE ' . $_zp_db->prefix('news') . ' SET `titlelink`=' . $_zp_db->quote($titlelink) . ' WHERE `id`=' . $id, false);
		if (!$rslt) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		} else {
			SearchEngine::clearSearchCache();
		}
	}
	// update article
	$article = new ZenpageNews($titlelink, true);
	$article->setTitle($title);
	$article->setContent($content);
	$article->setExtracontent($extracontent);
	$article->setCustomData(zp_apply_filter('save_article_custom_data', $custom, $article));
	$article->setPublished($show);
	$article->setDateTime($date);
	$article->setLastChange($date);
	$article->setCommentsAllowed($commentson);
	if (zp_loggedin(CODEBLOCK_RIGHTS)) {
		$article->setCodeblock($codeblock);
	}
	$article->setAuthor($author);
	$article->setPermalink($permalink);
	$article->setLocked($locked);
	$article->setExpiredate($expiredate);
	$article->setSticky(sanitize_numeric($_POST['sticky']));
	if (getcheckboxState('resethitcounter')) {
		$article->set('hitcounter', 0);
	}
	if (getcheckboxState('reset_rating')) {
		$article->set('total_value', 0);
		$article->set('total_votes', 0);
		$article->set('used_ips', 0);
	}
	$article->setTruncation(getcheckboxState('truncation'));
	processTags($article);
	$categories = array();
	$result2 = $_zp_db->queryFullArray("SELECT * FROM " . $_zp_db->prefix('news_categories') . " ORDER BY titlelink");
	foreach ($result2 as $cat) {
		if (isset($_POST["cat" . $cat['id']])) {
			$categories[] = $cat['titlelink'];
		}
	}
	$article->setCategories($categories);
	if (!$newarticle) {
		$article->setLastchangeUser($_zp_current_admin_obj->getUser());
	}
	if ($newarticle) {
		$msg = zp_apply_filter('new_article', '', $article);
		if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Article <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Article <em>%s</em> added"), $titlelink) . '</p>';
		}
	} else {
		$msg = zp_apply_filter('update_article', '', $article, $oldtitlelink);
		if (!$rslt) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("An article with the title/titlelink <em>%s</em> already exists!"), $titlelink) . '</p>';
		} else if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Article <em>%s</em> updated but you need to give it a <strong>title</strong> before publishing!"), get_language_string($titlelink)) . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Article <em>%s</em> updated"), $titlelink) . '</p>';
		}
	}
	$checkupdates = true;
	if($newarticle) {
		$checkupdates = false;
	}
	$article->save($checkupdates);
	if ($msg) {
		$reports[] = $msg;
	}
	return $article;
}

/**
 * Deletes an news article from the database
 *
 */
function deleteArticle($titlelink) {
	if (is_object($titlelink)) {
		$obj = $titlelink;
	} else {
		$obj = new ZenpageNews($titlelink);
	}
	$result = $obj->remove();
	if ($result) {
		if (is_object($titlelink)) {
			redirectURL(FULLWEBPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/zenpage/admin-news-articles.php?deleted');
		}
		SearchEngine::clearSearchCache();
		return "<p class='messagebox fade-message'>" . gettext("Article successfully deleted!") . "</p>";
	}
	return "<p class='errorbox fade-message'>" . gettext("Article delete failed!") . "</p>";
}

/**
 * Print the categories of a news article for the news articles list
 *
 * @param obj $obj object of the news article
 */
function printArticleCategories($obj) {
  $cat = $obj->getCategories();
  $number = 0;
  foreach ($cat as $cats) {
    $number++;
    if ($number != 1) {
      echo ", ";
    }
    echo get_language_string($cats['title']);
  }
}

/**
 * Print the categories of a news article for the news articles list
 *
 * @param obj $obj object of the news article
 */
function printPageArticleTags($obj) {
	$tags = $obj->getTags();
	$number = 0;
	foreach ($tags as $tag) {
		$number++;
		if ($number != 1) {
			echo ", ";
		}
		echo get_language_string($tag);
	}
}

/**
 * Prints the checkboxes to select and/or show the category of an news article on the edit or add page
 *
 * @param int $id ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 */
function printCategorySelection($id = '', $option = '') {
	global $_zp_zenpage, $_zp_db;

	$selected = '';
	echo "<ul class='zenpagechecklist'>\n";
	$all_cats = $_zp_zenpage->getAllCategories(false);
	foreach ($all_cats as $cats) {
		$catobj = new ZenpageCategory($cats['titlelink']);
		if ($option != "all") {
			$cat2news = $_zp_db->querySingleRow("SELECT cat_id FROM " . $_zp_db->prefix('news2cat') . " WHERE news_id = " . $id . " AND cat_id = " . $catobj->getID());
			if (isset($cat2news['cat_id']) && !empty($cat2news['cat_id'])) {
				$selected = "checked ='checked'";
			}
		}
		$catname = $catobj->getTitle();
		$catlink = $catobj->getName();
		if ($catobj->getPassword()) {
			$protected = '<img src="' . WEBPATH . '/' . ZENFOLDER . '/images/lock.png" alt="' . gettext('password protected') . '" />';
		} else {
			$protected = '';
		}
		$catid = $catobj->getID();
		echo "<li class=\"hasimage\" ><label for='cat" . $catid . "'><input name='cat" . $catid . "' id='cat" . $catid . "' type='checkbox' value='" . $catid . "' " . $selected . " />" . $catname . " " . $protected . "</label></li>\n";
	}
	echo "</ul>\n";
}

/**
 * Prints the dropdown menu for the date archive selector for the news articles list
 *
 */
function printArticleDatesDropdown($pagenumber) {
	global $_zp_zenpage;
	$datecount = $_zp_zenpage->getAllArticleDates();
	$lastyear = "";
	$nr = "";
	$option = getNewsAdminOption(array('category' => 0, 'published' => 0, 'sortorder' => 0, 'articles_page' => 1, 'author' => 0));
	if (!isset($_GET['date'])) {
		$selected = 'selected="selected"';
	} else {
		$selected = "";
	}
	?>
	<form name="articledatesdropdown" id="articledatesdropdown" style="float:left; margin-left: 10px;" action="#" >
		<select name="ListBoxURL" size="1" onchange="zp_gotoLink(this.form)">
			<?php
			echo "<option $selected value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('' => ''), $option)) . "'>" . gettext("View all months") . "</option>";
			foreach($datecount as $key => $val) {
				$nr++;
				if ($key == '0000-00-01') {
					$year = "no date";
					$month = "";
				} else {
					if (extension_loaded('intl') && getOption('date_format_localized')) {
						$year = zpFormattedDate('yyyy', $key, true); 
						$month = zpFormattedDate('MMMM', $key, true);
					} else {
						$year = zpFormattedDate('Y', $key, false); 
						$month = zpFormattedDate('F', $key,  false);
					}
				}
				if (isset($_GET['category'])) {
					$catlink = "&amp;category=" . sanitize($_GET['category']);
				} else {
					$catlink = "";
				}
				$check = $month . "-" . $year;
				if (isset($_GET['date']) AND $_GET['date'] == substr($key, 0, 7)) {
					$selected = "selected='selected'";
				} else {
					$selected = "";
				}
				echo "<option $selected value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('date' => substr($key, 0, 7)), $option)) . "'>$month $year ($val)</option>\n";
			}
			?>
		</select>
	</form>
	<?php
}

/**
 *
 * Compiles an option parameter list
 * @param array $test array of parameter=>type elements. type=0:string type=1:numeric
 * @return array
 */
function getNewsAdminOption($test) {
	$list = array();
	foreach ($test as $item => $type) {
		if (isset($_GET[$item])) {
			if ($type) {
				$list[$item] = (int) sanitize_numeric($_GET[$item]);
			} else {
				$list[$item] = sanitize($_GET[$item]);
			}
		}
	}
	return $list;
}

/**
 * Creates the admin paths for news articles if you use the dropdowns on the admin news article list together
 *
 * @param array $list an parameter array of item=>value for instance, the result of getNewsAdminOption()
 * @return string
 */
function getNewsAdminOptionPath($list) {
	$optionpath = '';
	$char = '?';
	foreach ($list as $p => $q) {
		if ($q) {
			$optionpath .= $char . $p . '=' . $q;
		} else {
			$optionpath .= $char . $p;
		}
		$char = '&amp;';
	}
	return $optionpath;
}

/**
 * Prints the dropdown menu for the published/un-publishd selector for the news articles list
 *
 */
function printUnpublishedDropdown() {
	global $_zp_zenpage;
	?>
	<form name="unpublisheddropdown" id="unpublisheddropdown" style="float: left; margin-left: 10px;"	action="#">
		<select name="ListBoxURL" size="1"	onchange="zp_gotoLink(this.form)">
			<?php
			$all = "";
			$published = "";
			$unpublished = "";
			$sticky = '';
			if (isset($_GET['published'])) {
				switch ($_GET['published']) {
					case "no":
						$unpublished = "selected='selected'";
						break;
					case "yes":
						$published = "selected='selected'";
						break;
					case 'sticky':
						$sticky = "selected='selected'";
						break;
				}
			} else {
				$all = "selected='selected'";
			}
			$option = getNewsAdminOption(array('category' => 0, 'date' => 0, 'sortorder' => 0, 'articles_page' => 1, 'author' => 0));
			echo "<option $all value='admin-news-articles.php" . getNewsAdminOptionPath($option) . "'>" . gettext("All articles") . "</option>\n";
			echo "<option $published value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('published' => 'yes'), $option)) . "'>" . gettext("Published") . "</option>\n";
			echo "<option $unpublished value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('published' => 'no'), $option)) . "'>" . gettext("Un-published") . "</option>\n";
			echo "<option $sticky value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('published' => 'sticky'), $option)) . "'>" . gettext("Sticky") . "</option>\n";
			?>
		</select>
	</form>
	<?php
}

/**
 * Prints the dropdown menu for the sortorder selector for the news articles list
 *
 */
function printSortOrderDropdown() {
	global $_zp_zenpage;
	?>
	<form name="sortorderdropdown" id="sortorderdropdown" style="float: left; margin-left: 10px;"	action="#">
		<select name="ListBoxURL" size="1"	onchange="zp_gotoLink(this.form)">
			<?php
			$orderdate_desc = '';
			$orderdate_asc = '';
			$ordertitle_desc = '';
			$ordertitle_asc = '';
			$orderlastchange_desc = '';
			$orderlastchange_asc = '';
			if (isset($_GET['sortorder'])) {
				switch ($_GET['sortorder']) {
					case "date-desc":
						$orderdate_desc = "selected='selected'";
						break;
					case "date-asc":
						$orderdate_asc = "selected='selected'";
						break;
					case "title-desc":
						$ordertitle_desc = "selected='selected'";
						break;
					case "title-asc":
						$ordertitle_asc = "selected='selected'";
						break;
					case "lastchange-desc":
						$orderlastchange_desc = "selected='selected'";
						break;
					case "lastchange-asc":
						$orderlastchange_asc = "selected='selected'";
						break;
				}
			} else {
				$orderdate_desc = "selected='selected'";
			}
			$option = getNewsAdminOption(array('category' => 0, 'date' => 0, 'published' => 0, 'articles_page' => 1, 'author' => 0));
			echo "<option $orderdate_desc value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('sortorder' => 'date-desc'), $option)) . "'>" . gettext("Order by date descending") . "</option>\n";
			echo "<option $orderdate_asc value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('sortorder' => 'date-asc'), $option)) . "'>" . gettext("Order by date ascending") . "</option>\n";
			echo "<option $ordertitle_desc value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('sortorder' => 'title-desc'), $option)) . "'>" . gettext("Order by title descending") . "</option>\n";
			echo "<option $ordertitle_asc value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('sortorder' => 'title-asc'), $option)) . "'>" . gettext("Order by title ascending") . "</option>\n";
			echo "<option $orderlastchange_desc value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('sortorder' => 'lastchange-desc'), $option)) . "'>" . gettext("Order by last change date descending") . "</option>\n";
			echo "<option $orderlastchange_asc value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('sortorder' => 'lastchange-asc'), $option)) . "'>" . gettext("Order by last change date ascending") . "</option>\n";
			?>
		</select>
	</form>
	<?php
}

/**
 * Prints the dropdown menu for the category selector for the news articles list
 *
 */
function printCategoryDropdown() {
	global $_zp_zenpage;
	$result = $_zp_zenpage->getAllCategories(false);
	if (isset($_GET['date'])) {
		$datelink = "&amp;date=" . sanitize($_GET['date']);
		$datelinkall = "?date=" . sanitize($_GET['date']);
	} else {
		$datelink = "";
		$datelinkall = "";
	}

	if (isset($_GET['category'])) {
		$selected = '';
		$category = sanitize($_GET['category']);
	} else {
		$selected = "selected='selected'";
		$category = "";
	}
	?>
	<form name ="categorydropdown" id="categorydropdown" style="float:left" action="#" >
		<select name="ListBoxURL" size="1" onchange="zp_gotoLink(this.form)">
			<?php
			$option = getNewsAdminOption(array('date' => 0, 'published' => 0, 'sortorder' => 0, 'articles_page' => 1, 'author' => 0));
			echo "<option $selected value='admin-news-articles.php" . getNewsAdminOptionPath($option) . "'>" . gettext("All categories") . "</option>\n";

			foreach ($result as $cat) {
				$catobj = new ZenpageCategory($cat['titlelink']);
				// check if there are articles in this category. If not don't list the category.
				$count = count($catobj->getArticles(0, 'all', false, null, null, null, null, false));
				$count = " (" . $count . ")";
				if ($category == $cat['titlelink']) {
					$selected = "selected='selected'";
				} else {
					$selected = "";
				}
				//This is much easier than hacking the nested list function to work with this
				$getparents = $catobj->getParents();
				$levelmark = '';
				foreach ($getparents as $parent) {
					$levelmark .= '» ';
				}
				$title = $catobj->getTitle();
				if (empty($title)) {
					$title = '*' . $catobj->getName() . '*';
				}
				if ($count != " (0)") {
					echo "<option $selected value='admin-news-articles.php" . getNewsAdminOptionPath(array_merge(array('category' => $catobj->getName()), $option)) . "'>" . $levelmark . $title . $count . "</option>\n";
				}
			}
			?>
		</select>
	</form>
	<?php
}

/**
 * Prints the dropdown menu for the articles per page selector for the news articles list
 *
 */
function printArticlesPerPageDropdown($pagenumber, $articles_page) {
	global $_zp_zenpage;
	?>
	<form name="articlesperpagedropdown" id="articlesperpagedropdown" method="POST" style="float: left; margin-left: 10px;" action="#">
		<select name="ListBoxURL" size="1"	onchange="zp_gotoLink(this.form)">
			<?php
			$option = getNewsAdminOption(array('category' => 0, 'date' => 0, 'published' => 0, 'sortorder' => 0, 'author' => 0));
			$list = array_unique(array(15, 30, 60, max(1, getOption('articles_per_page'))));
			sort($list);
			foreach ($list as $count) {
				?>
				<option <?php if ($articles_page == $count) echo 'selected="selected"'; ?> value="admin-news-articles.php<?php echo getNewsAdminOptionPath(array_merge(array('articles_page' => $count, 'pagenumber' => (int) ($pagenumber * $articles_page / $count)), $option)); ?>"><?php printf(gettext('%u per page'), $count); ?></option>
				<?php
			}
			?>
			<option <?php if ($articles_page == 0) echo 'selected="selected"'; ?> value="admin-news-articles.php<?php echo getNewsAdminOptionPath(array_merge(array('articles_page' => 'all'), $option)); ?>"><?php echo gettext("All"); ?></option>

		</select>
		&nbsp;&nbsp;
	</form>
	<?php
}

/**
 * Prints the dropdown menu all authors that currently are authors of news articles
 */
function printAuthorDropdown() {
	$authors = Zenpage::getAllAuthors();
	$selected = "selected='selected'";
	if (isset($_GET['author'])) {
		$current_author = sanitize($_GET['author']);
	} else {
		$current_author = "";
	}
	?>
	<form name="newssauthorsdropdown" id="newssauthorsdropdown" method="POST" style="float: left; margin-left: 10px;"	action="#">
		<select name="ListBoxURL" size="1"	onchange="zp_gotoLink(this.form)">
			<?php
			$option = getNewsAdminOption(array('category' => 0, 'date' => 0, 'published' => 0, 'articles_page' => 1, 'sortorder' => 0));			
			foreach ($authors as $author) {
				?>
				<option <?php if ($current_author == $author) echo $selected; ?>value="admin-news-articles.php<?php echo getNewsAdminOptionPath(array_merge(array('author' => $author), $option)); ?>"><?php echo $author; ?></option>
				<?php
			}
			?>
			<option <?php if ($current_author == 'all') echo $selected; ?>value="admin-news-articles.php<?php echo getNewsAdminOptionPath(array_merge(array('author' => 'all'), $option)); ?>"><?php echo gettext("All authors"); ?></option>
		</select>
		&nbsp;&nbsp;
	</form>
	<?php
}

/* * ************************
  /* Category functions
 * ************************* */

/**
 * Updates or adds a category
 *
 * @param array $reports the results display
 * @param bool $newcategory true if a new article
 *
 */
function updateCategory(&$reports, $newcategory = false) {
	global $_zp_zenpage, $_zp_current_admin_obj, $_zp_db;
	$date = date('Y-m-d_H-i-s');
	$id = sanitize_numeric($_POST['id']);
	$permalink = getcheckboxState('permalink');
	$title = process_language_string_save("title", 2);
	$desc = process_language_string_save("desc", EDITOR_SANITIZE_LEVEL);
	$custom = process_language_string_save("custom_data", 1);
	if ($newcategory) {
		$titlelink = createTitlelink($title, $date);
		if(getOption('zenpage_titlelinkdate_categories')) {
			$titlelink = addDateToTitlelink($titlelink);
		}
		$duplicate = checkTitlelinkDuplicate($titlelink, 'category');
		if ($duplicate) {
			//already exists
			$titlelink = addDateToTitlelink($titlelink);
			$reports[] = "<p class='warningbox fade-message'>" . gettext('Duplicate category title') . '</p>';
		}
		$oldtitlelink = $titlelink;
	} else {
		$titlelink = $oldtitlelink = sanitize($_POST['titlelink-old'], 3);
		if (getcheckboxState('edittitlelink')) {
			$titlelink = sanitize($_POST['titlelink'], 3);
			if (empty($titlelink)) {
				$titlelink = createTitlelink($title, $date);
			}
		} else {
			if (!$permalink) { //	allow the link to change
				$link = seoFriendly(get_language_string($title));
				if (!empty($link)) {
					$titlelink = $link;
				}
			}
		}
	}
	$titleok = true;
	if ($titlelink != $oldtitlelink) { // title link change must be reflected in DB before any other updates
		$titleok = $_zp_db->query('UPDATE ' . $_zp_db->prefix('news_categories') . ' SET `titlelink`=' . $_zp_db->quote($titlelink) . ' WHERE `id`=' . $id, false);
		if (!$titleok) {
			$titlelink = $oldtitlelink; // force old link so data gets saved
		} else {
			SearchEngine::clearSearchCache();
		}
	}
	//update category
	$show = getcheckboxState('show');
	$cat = new ZenpageCategory($titlelink, true);
	$notice = processCredentials($cat);
	$cat->setPermalink(getcheckboxState('permalink'));
	$cat->set('title', $title);
	$cat->setDesc($desc);
	$cat->setLastChange();
	$cat->setCustomData(zp_apply_filter('save_category_custom_data', $custom, $cat));
	$cat->setPublished($show);
	if (getcheckboxState('resethitcounter')) {
		$cat->set('hitcounter', 0);
	}
	if (getcheckboxState('reset_rating')) {
		$cat->set('total_value', 0);
		$cat->set('total_votes', 0);
		$cat->set('used_ips', 0);
	}
	if ($newcategory) {
		$cat->setDefaultSortorder();
		$msg = zp_apply_filter('new_category', '', $cat);
		if (empty($title)) {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("Category <em>%s</em> added but you need to give it a <strong>title</strong> before publishing!"), $titlelink) . '</p>';
		} else if ($notice == '?mismatch=user') {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Category user') . '</p>';
		} else if ($notice) {
			$reports[] = "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
		} else {
			$reports[] = "<p class='messagebox fade-message'>" . sprintf(gettext("Category <em>%s</em> added"), $titlelink) . '</p>';
		}
	} else {
		$cat->setLastchangeUser($_zp_current_admin_obj->getUser());
		$msg = zp_apply_filter('update_category', '', $cat, $oldtitlelink);
		if ($titleok) {
			if (empty($titlelink) OR empty($title)) {
				$reports[] = "<p class='errorbox fade-message'>" . gettext("You forgot to give your category a <strong>title or titlelink</strong>!") . "</p>";
			} else if ($notice == '?mismatch=user') {
				$reports[] = "<p class='errorbox fade-message'>" . gettext('You must supply a password for the Protected Category user') . '</p>';
			} else if ($notice) {
				$reports[] = "<p class='errorbox fade-message'>" . gettext('Your passwords were empty or did not match') . '</p>';
			} else {
				$reports[] = "<p class='messagebox fade-message'>" . gettext("Category updated!") . "</p>";
			}
		} else {
			$reports[] = "<p class='errorbox fade-message'>" . sprintf(gettext("A category with the title/titlelink <em>%s</em> already exists!"), html_encode($cat->getTitle())) . "</p>";
		}
	}
	$checkupdates = true;
	if ($newcategory) { 
		$checkupdates = false;
	}
	$cat->save($checkupdates);
	if ($msg) {
		$reports[] = $msg;
	}
	return $cat;
}

/**
 * Deletes a category (and also if existing its subpages) from the database
 *
 */
function deleteCategory($titlelink) {
	$obj = new ZenpageCategory($titlelink);
	$result = $obj->remove();
	if ($result) {
		SearchEngine::clearSearchCache();
		return "<p class='messagebox fade-message'>" . gettext("Category successfully deleted!") . "</p>";
	}
	return "<p class='errorbox fade-message'>" . gettext("Category  delete failed!") . "</p>";
}

/**
 * Prints the list entry of a single category for the sortable list
 *
 * @param array $cat Array storing the db info of the category
 * @param string $flag If the category is protected
 * @return string
 */
function printCategoryListSortableTable($cat, $flag) {
	global $_zp_zenpage;
	if ($flag) {
		$img = '../../images/drag_handle_flag.png';
	} else {
		$img = '../../images/drag_handle.png';
	}
	$count = count($cat->getArticles(0, false));
	if ($cat->getTitle()) {
		$cattitle = $cat->getTitle();
	} else {
		$cattitle = "<span style='color:red; font-weight: bold'> <strong>*</strong>" . $cat->getName() . "*</span>";
	}
	?>
	<div class='page-list_row'>
		<div class='page-list_title' >
			<?php echo "<a href='admin-edit.php?newscategory&amp;titlelink=" . $cat->getName() . "' title='" . gettext('Edit this category') . "'>" . $cattitle . "</a>" . checkHitcounterDisplay($cat->getHitcounter()); ?>
		</div>
		<div class="page-list_extra">
			<?php echo $count; ?>
			<?php echo gettext("articles"); ?>
		</div>

		<div class="page-list_iconwrapper">
			<div class="page-list_icon">
				<?php printProtectedIcon($cat); ?>
			</div>
			<div class="page-list_icon">
				<?php printPublishIconLink($cat, 'newscategory'); ?>
			</div>
			<div class="page-list_icon">
				<?php if ($count == 0) { ?>
					<img src="../../images/icon_inactive.png" alt="<?php gettext('locked'); ?>" />
					<?php
				} else {
					?>
					<a href="../../../index.php?p=news&amp;category=<?php echo js_encode($cat->getName()); ?>" title="<?php echo gettext("View category"); ?>">
						<img src="images/view.png" alt="view" />
					</a>
				<?php } ?>
			</div>
			<?php
			if (extensionEnabled('hitcounter')) {
				?>
				<div class="page-list_icon"><a
						href="?hitcounter=1&amp;id=<?php echo $cat->getID(); ?>&amp;tab=categories&amp;XSRFToken=<?php echo getXSRFToken('hitcounter') ?>"
						title="<?php echo gettext("Reset hitcounter"); ?>"> <img
							src="../../images/reset.png"
							alt="<?php echo gettext("Reset hitcounter"); ?>" /> </a>
				</div>
				<?php
			}
			?>
			<div class="page-list_icon"><a
					href="javascript:confirmDelete('admin-categories.php?delete=<?php echo js_encode($cat->getName()); ?>&amp;tab=categories&amp;XSRFToken=<?php echo getXSRFToken('delete_category') ?>',deleteCategory)"
					title="<?php echo gettext("Delete Category"); ?>"><img
						src="../../images/fail.png" alt="<?php echo gettext("Delete"); ?>"
						title="<?php echo gettext("Delete Category"); ?>" /></a>
			</div>
			<div class="page-list_icon"><input class="checkbox" type="checkbox" name="ids[]" value="<?php echo $cat->getName(); ?>"
																				 onclick="triggerAllBox(this.form, 'ids[]', this.form.allbox);" />
			</div>
		</div>
	</div>
	<?php
}

/**
 * Prints the checkboxes to select and/or show the category of an news article on the edit or add page
 *
 * @param int $id ID of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * @param string $option "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 */
function printCategoryCheckboxListEntry($cat, $articleid, $option, $class = '') {
	global $_zp_db;
	$selected = '';
	if (($option != "all") && !$cat->transient && !empty($articleid)) {
		$cat2news = $_zp_db->querySingleRow("SELECT cat_id FROM " . $_zp_db->prefix('news2cat') . " WHERE news_id = " . $articleid . " AND cat_id = " . $cat->getID());
		$selected = "";
		if (isset($cat2news['cat_id']) && !empty($cat2news['cat_id'])) {
			$selected = "checked ='checked'";
		}
	}
	$catname = $cat->getTitle();
	$catlink = $cat->getName();
	/*if ($cat->getPassword()) {
		$protected = '<img src="' . WEBPATH . '/' . ZENFOLDER . '/images/lock.png" alt="' . gettext('password protected') . '" />';
	} else {
		$protected = '';
	} */
	$catid = $cat->getID();
	echo '<label for="cat' . $catid . '"><input name="cat' . $catid . '" class="' . $class . '" id="cat' . $catid . '" type="checkbox" value="' . $catid . '"' . $selected . ' />' . $catname;
	printProtectedIcon($cat);
	echo "</label>\n";
}

/* * ************************
  /* General functions
 * ************************* */

/**
 * Prints the nested list for pages and categories
 *
 * @param string $listtype 'cats-checkboxlist' for a fake nested checkbock list of categories for the news article edit/add page
 * 												'cats-sortablelist' for a sortable nested list of categories for the admin categories page
 * 												'pages-sortablelist' for a sortable nested list of pages for the admin pages page
 * @param $obj $obj Optional, default empty. Passing an articledid is deprecated and will be removed in ZenphotoCMS 2.0.
 * - listtype = 'cats-checkboxlist': Object of the news article if the categories an existing articles is assigned to shall be shown, empty if this is a new article to be added.
 * - listtype = 'pages-sortablelist': Object of the page object to show sub pages of or empty for all 
 * @param string $option Only for $listtype = 'cats-checkboxlist': "all" to show all categories if creating a new article without categories assigned, empty if editing an existing article that already has categories assigned.
 * @return string | bool
 */
function printNestedItemsList($listtype = 'cats-sortablelist', $obj = '', $option = '', $class = 'nestedItem') {
	global $_zp_zenpage;
	if ($listtype == 'cats-sortablelist' && is_int($obj)) {
		deprecationNotice(gettext('The 2nd parameter of printNestedItemsList() should be a Zenpage news or page object and not an integer (to be removed in ZenphotoCMS 2.0'), '$obj');
		$obj = getItemByID($obj, 'news');
	}
	$id = '';
	if (is_object($obj)) {
		$id = $obj->getID();
	}
	switch ($listtype) {
		case 'cats-checkboxlist':
		default:
			$ulclass = "";
			break;
		case 'cats-sortablelist':
		case 'pages-sortablelist':
			$ulclass = " class=\"page-list\"";
			break;
	}
	switch ($listtype) {
		case 'cats-checkboxlist':
		case 'cats-sortablelist':
			//Without this the order is incorrect until the 2nd page reload…
			$_zp_zenpage = new Zenpage();
			$items = $_zp_zenpage->getAllCategories(false);
			break;
		case 'pages-sortablelist':
			if (is_object($obj)) {
				$items = $obj->getPages(false);
			} else {
				$items = $_zp_zenpage->getPages(false);
			}
			break;
		default:
			$items = array();
			break;
	}
	//echo "<pre>"; print_r($items); echo "</pre>";
	$indent = 1;
	$open = array(1 => 0);
	$rslt = false;
	foreach ($items as $item) {
		switch ($listtype) {
			case 'cats-checkboxlist':
			case 'cats-sortablelist':
				$itemobj = new ZenpageCategory($item['titlelink']);
				$ismypage = $itemobj->isMyItem(ZENPAGE_NEWS_RIGHTS);
				break;
			case 'pages-sortablelist':
				$itemobj = new ZenpagePage($item['titlelink']);
				$ismypage = $itemobj->isMyItem(ZENPAGE_PAGES_RIGHTS);
				break;
		}
		$itemsortorder = $itemobj->getSortOrder();
		$itemid = $itemobj->getID();
		if ($ismypage) {
			$order = explode('-', strval($itemsortorder));
			$level = max(1, count($order));
			if ($toodeep = $level > 1 && $order[$level - 1] === '') {
				$rslt = true;
			}
			if ($level > $indent) {
				echo "\n" . str_pad("\t", $indent, "\t") . "<ul" . $ulclass . ">\n";
				$indent++;
				$open[$indent] = 0;
			} else if ($level < $indent) {
				while ($indent > $level) {
					$open[$indent] --;
					$indent--;
					echo "</li>\n" . str_pad("\t", $indent, "\t") . "</ul>\n";
				}
			} else { // indent == level
				if ($open[$indent]) {
					echo str_pad("\t", $indent, "\t") . "</li>\n";
					$open[$indent] --;
				} else {
					echo "\n";
				}
			}
			if ($open[$indent]) {
				echo str_pad("\t", $indent, "\t") . "</li>\n";
				$open[$indent] --;
			}
			switch ($listtype) {
				case 'cats-checkboxlist':
					echo "<li>\n";
					printCategoryCheckboxListEntry($itemobj, $id, $option, $class);
					break;
				case 'cats-sortablelist':
					echo str_pad("\t", $indent - 1, "\t") . "<li id=\"id_" . $itemid . "\">";
					printCategoryListSortableTable($itemobj, $toodeep);
					break;
				case 'pages-sortablelist':
					echo str_pad("\t", $indent - 1, "\t") . "<li id=\"id_" . $itemid . "\">";
					printPagesListTable($itemobj, $toodeep);
					break;
			}
			$open[$indent] ++;
		}
	}
	while ($indent > 1) {
		echo "</li>\n";
		$open[$indent] --;
		$indent--;
		echo str_pad("\t", $indent, "\t") . "</ul>";
	}
	if ($open[$indent]) {
		echo "</li>\n";
	} else {
		echo "\n";
	}
	return $rslt;
}

/**
 * Updates the sortorder of the items list in the database
 *
 * @param string $mode 'pages' or 'categories'
 * @return array
 */
function updateItemSortorder($mode = 'pages') {
	global $_zp_db;
	if (!empty($_POST['order'])) { // if someone didn't sort anything there are no values!
		$order = processOrder($_POST['order']);
		$parents = array('NULL');
		foreach ($order as $id => $orderlist) {
			$id = str_replace('id_', '', $id);
			$level = count($orderlist);
			$parents[$level] = $id;
			$myparent = $parents[$level - 1];
			switch ($mode) {
				case 'pages':
					$dbtable = $_zp_db->prefix('pages');
					break;
				case 'categories':
					$dbtable = $_zp_db->prefix('news_categories');
					break;
			}
			$sql = "UPDATE " . $dbtable . " SET `sort_order` = " . $_zp_db->quote(implode('-', $orderlist)) . ", `parentid`= " . $myparent . " WHERE `id`=" . $id;
			$_zp_db->query($sql);
		}
		return true;
	}
	return false;
}

/**
 * Checks if no title has been provide for items on new item creation
 * @param string $titlefield The title field
 * @param string $type 'page', 'news' or 'category'
 * @return string
 */
function checkForEmptyTitle($titlefield, $type, $truncate = true) {
	switch ($type) {
		case "page":
			$text = gettext("Untitled page");
			break;
		case "news":
			$text = gettext("Untitled article");
			break;
		case "category":
			$text = gettext("Untitled category");
			break;
	}
	$title = getBare($titlefield);
	if ($title) {
		if ($truncate) {
			$title = truncate_string($title, 40);
		}
	} else {
		$title = "<span style='color:red; font-weight: bold'>" . $text . "</span>";
	}
	echo $title;
}

/**
 * Publishes a page or news article
 *
 * @param object $obj
 * @param int $show the value for publishing
 * @return string
 */
function zenpagePublish($obj, $show) {
	global $_zp_current_admin_obj;
	$obj->setPublished((int) ($show && 1));
	$obj->setLastchangeUser($_zp_current_admin_obj->getUser());
	$obj->save();
}

/**
 * Skips the scheduled future publishing by setting the date of a page or article to the current date to publish it immediately
 * or the expiration handling by setting the expiredate to null.
 *
 * @since 1.5.7
 * @param object $obj
 * @param string $type "futuredate" or "expiredate"
 * @return string
 */
function skipScheduledPublishing($obj, $type = 'futuredate') {
	global $_zp_current_admin_obj;
	switch ($type) {
		case 'futuredate':
			$obj->setDateTime(date('Y-m-d H:i:s'));
			$obj->setPublished(1);
			break;
		case 'expiredate':
			$obj->setExpiredate(null);
			$obj->setPublished(1);
			break;
	}
	$obj->setLastchangeUser($_zp_current_admin_obj->getUser());
	$obj->save();
}

/**
 * Checks if there are hitcounts and if they are displayed behind the news article, page or category title
 *
 * @param string $item The array of the current news article, page or category in the list.
 * @return string
 */
function checkHitcounterDisplay($item) {
	if ($item == 0) {
		$hitcount = "";
	} else {
		if ($item == 1) {
			$hits = gettext("hit");
		} else {
			$hits = gettext("hits");
		}
		$hitcount = " (" . $item . " " . $hits . ")";
	}
	return $hitcount;
}

/**
 * returns an array of how many pages, articles, categories and news or pages comments we got.
 *
 * @param string $option What the statistic should be shown of: "news", "pages", "categories"
 */
function getNewsPagesStatistic($option) {
	global $_zp_zenpage;
	switch ($option) {
		case "news":
			$items = $_zp_zenpage->getArticles();
			$type = gettext("Articles");
			break;
		case "pages":
			$items = $_zp_zenpage->getPages(false);
			$type = gettext("Pages");
			break;
		case "categories":
			$type = gettext("Categories");
			$items = $_zp_zenpage->getAllCategories(false);
			break;
	}
	$total = count($items);
	$pub = 0;
	foreach ($items as $item) {
		switch ($option) {
			case "news":
				$itemobj = new ZenpageNews($item['titlelink']);
				break;
			case "pages":
				$itemobj = new ZenpagePage($item['titlelink']);
				break;
			case "categories":
				$itemobj = new ZenpageCategory($item['titlelink']);
				break;
		}
		if ($itemobj->isPublished()) {
			$pub++;
		}
	}
	$unpub = $total - $pub;
	return array($total, $type, $unpub);
}

function printPagesStatistic() {
	list($total, $type, $unpub) = getNewsPagesStatistic("pages");
	if (empty($unpub)) {
		printf(ngettext('<strong>%1$u</strong> page', '<strong>%1$u</strong> pages', $total), $total);
	} else {
		printf(ngettext('<strong>%1$u</strong> page (<strong>%2$u</strong> un-published)', '<strong>%1$u</strong> pages (<strong>%2$u</strong> un-published)', $total), $total, $unpub);
	}
}

function printNewsStatistic() {
	list($total, $type, $unpub) = getNewsPagesStatistic("news");
	if (empty($unpub)) {
		printf(ngettext('<strong>%1$u</strong> article', '<strong>%1$u</strong> articles', $total), $total);
	} else {
		printf(ngettext('<strong>%1$u</strong> article (<strong>%2$u</strong> un-published)', '<strong>%1$u</strong> articles (<strong>%2$u</strong> un-published)', $total), $total, $unpub);
	}
}

function printCategoriesStatistic() {
	list($total, $type, $unpub) = getNewsPagesStatistic("categories");
	if (empty($unpub)) {
		printf(ngettext('<strong>%1$u</strong> category', '<strong>%1$u</strong> categories', $total), $total);
	} else {
		printf(ngettext('<strong>%1$u</strong> category (<strong>%2$u</strong> un-published)', '<strong>%1$u</strong> categories (<strong>%2$u</strong> un-published)', $total), $total, $unpub);
	}
}

/**
 * Prints the links to JavaScript and CSS files zenpage needs.
 * Actually the same as for zenphoto but with different paths since we are in the plugins folder.
 *
 * @param bool $sortable set to true for tabs with sorts.
 *
 */
function zenpageJSCSS() {
	?>
	<link rel="stylesheet" href="zenpage.css" type="text/css" />
	<script>
		$(document).ready(function() {
			$("#tip a").click(function() {
				$("#tips").toggle("slow");
			});
		});
	</script>
	<?php
}

function printZenpageIconLegend() {
	?>
	<ul class="iconlegend">
		<?php
		if (GALLERY_SECURITY == 'public') {
			?>
			<li><?php echo getStatusIcon('protected') . getStatusIcon('protected_by_parent').  gettext("Password protected/Password protected by parent"); ?></li>
			<li><?php echo getStatusIcon('published') . getStatusIcon('unpublished') . getStatusIcon('unpublished_by_parent'); ?><?php echo gettext("Published/Unpublished/Unpublished by parent"); ?></li>
			<li><?php echo getStatusIcon('publishschedule') . getStatusIcon('expiration') . getStatusIcon('expired'); ?><?php echo gettext("Scheduled publishing/Scheduled expiration/Expired"); ?></li>
			<?php
		}
		?>
		<li><img src="../../images/comments-on.png" alt="" /><img src="../../images/comments-off.png" alt="" /><?php echo gettext("Comments on/off"); ?></li>
		<li><img src="../../images/view.png" alt="" /><?php echo gettext("View"); ?></li>
		<?php
		if (extensionEnabled('hitcounter')) {
			?>
			<li><img src="../../images/reset.png" alt="" /><?php echo gettext("Reset hitcounter"); ?></li>
			<?php
		}
		?>
		<li><img src="../../images/fail.png" alt="" /><?php echo gettext("Delete"); ?></li>
	</ul>
	<?php
}

/**
 * Prints a dropdown to select the author of a page or news article (Admin rights users only)
 *
 * @param string $currentadmin The current admin is selected if adding a new article, otherwise the original author
 */
function authorSelector($author = NULL) {
	global $_zp_authority, $_zp_current_admin_obj;
	if (empty($author)) {
		$author = $_zp_current_admin_obj->getUser();
	}
	$authors = array($author => $author);
	if (zp_loggedin(MANAGE_ALL_PAGES_RIGHTS | MANAGE_ALL_NEWS_RIGHTS)) {
		$admins = $_zp_authority->getAdministrators();
		foreach ($admins as $admin) {
			if ($admin['rights'] & (ADMIN_RIGHTS | ZENPAGE_PAGES_RIGHTS | ZENPAGE_NEWS_RIGHTS)) {
				$authors[$admin['user']] = $admin['user'];
			}
		}
	}
	?>
	<select size='1' name="author" id="author">
		<?php
		generateListFromArray(array($author), $authors, false, false);
		?>
	</select>
	<?php
}



/**
 * Prints the publish/un-published/scheduled publishing icon with a link for the pages and news articles list.
 * 
 * @since 1.6.1 
 *
 * @param obj $obj Object of the page or news article to check
 */
function printPublishIconLink($obj, $type = '', $linkback = '') {
	$urladd = '';
	if ($obj->table == 'news') {
		if (isset($_GET['subpage'])) {
			$urladd .= "&amp;subpage=" . sanitize($_GET['subpage']);
		}
		if (isset($_GET['date'])) {
			$urladd .= "&amp;date=" . sanitize($_GET['date']);
		}
		if (isset($_GET['category'])) {
			$urladd .= "&amp;category=" . sanitize($_GET['category']);
		}
		if (isset($_GET['sortorder'])) {
			$urladd .= "&amp;sortorder=" . sanitize($_GET['sortorder']);
		}
		if (isset($_GET['articles_page'])) {
			$urladd .= "&amp;articles_page=" . sanitize_numeric($_GET['articles_page']);
		}
	}
	if ($obj->hasPublishSchedule()) {
		$title = gettext("Publish immediately (skip scheduling)");
		$action = '?skipscheduling=1';
	} else if ($obj->hasExpiration()) {
		$title = gettext("Skip scheduled expiration");
		$action = '?skipexpiration=1';
	} else if ($obj->isPublished()) {
		if ($obj->isUnpublishedByParent()) {
			$title = gettext("Unpublish") .' - ' . getStatusNote('unpublished_by_parent');
			$action = '?publish=0';
		} else {
			$title = gettext("Unpublish");
			$action = '?publish=0';
		}
	} else if (!$obj->isPublished()) {
		if ($obj->hasExpired()) {
			$title = gettext("Publish immediately (skip expiration)");
			$action = '?skipexpiration=1';
		} else {
			$title = gettext("Publish");
			$action = '?publish=1';
		}
	}
	?>
	<a href="<?php echo $action; ?>&amp;titlelink=<?php echo html_encode($obj->getName()) . $urladd; ?>&amp;XSRFToken=<?php echo getXSRFToken('update') ?>">
		<?php echo getPublishIcon($obj); ?>
	</a>
	<?php
}

	/**
	 * Checks if a checkbox is selected and checks it if.
	 *
	 * @param string $field the array field of an item array to be checked (for example "permalink" or "comments on")
	 */
	function checkIfChecked($field) {
		if ($field) {
			echo 'checked="checked"';
		}
	}

	/**
	 * Checks if the current logged in admin user is the author that locked the page/article.
	 * Only that author or any user with admin rights will be able to edit or unlock.
	 *
	 * @param object $page The array of the page or article to check
	 * @return bool
	 */
	function checkIfLockedPage($page) {
		global $_zp_current_admin_obj;
		if (zp_loggedin(ADMIN_RIGHTS))
			return true;
		if ($page->getLocked()) {
			return $_zp_current_admin_obj->getUser() == $page->getAuthor() && $page->isMyItem(ZENPAGE_PAGES_RIGHTS);
		} else {
			return true;
		}
	}

	/**
	 * Checks if the current logged in admin user is the author that locked the article.
	 * Only that author or any user with admin rights will be able to edit or unlock.
	 *
	 * @param object $page The array of the page or article to check
	 * @return bool
	 */
	function checkIfLockedNews($news) {
		global $_zp_current_admin_obj;
		if (zp_loggedin(ADMIN_RIGHTS))
			return true;
			
		if ($news->getLocked()) {
			return $_zp_current_admin_obj->getUser() == $news->getAuthor() && $news->isMyItem(ZENPAGE_NEWS_RIGHTS);
		} else {
			return true;
		}
	}

	/**
	 * Checks if the current admin-edit.php page is called for news articles or for pages.
	 *
	 * @param string $page What you want to check for, "page" or "newsarticle"
	 * @return bool
	 */
	function is_AdminEditPage($page) {
		return isset($_GET[$page]);
	}

	/**
	 * Processes the check box bulk actions
	 *
	 */
	function processZenpageBulkActions($type) {
		global $_zp_zenpage, $_zp_current_admin_obj;
		$action = false;
		if (isset($_POST['ids'])) {
			//echo "action for checked items:". $_POST['checkallaction'];
			$action = sanitize($_POST['checkallaction']);
			$links = sanitize($_POST['ids']);
			$total = count($links);
			$message = NULL;
			$sql = '';
			if ($action != 'noaction') {
				if ($total > 0) {
					if ($action == 'addtags' || $action == 'alltags') {
						$tags = bulkTags();
					}
					if ($action == 'addcats') {
						foreach ($_POST as $key => $value) {
							$key = postIndexDecode($key);
							if (substr($key, 0, 3) == 'cat') {
								if ($value) {
									$cats[] = substr($key, 3);
								}
							}
						}
						$cats = sanitize($cats, 3);
					}
					$n = 0;
					foreach ($links as $titlelink) {
						$class = 'Zenpage' . $type;
						$obj = new $class($titlelink);

						switch ($action) {
							case 'deleteall':
								$obj->remove();
								SearchEngine::clearSearchCache();
								break;
							case 'addtags':
								$mytags = array_unique(array_merge($tags, $obj->getTags()));
								$obj->setTags($mytags);
								break;
							case 'cleartags':
								$obj->setTags(array());
								break;
							case 'alltags':
								$allarticles = $obj->getArticles('', 'all', true);
								foreach ($allarticles as $article) {
									$newsobj = new ZenpageNews($article['titlelink']);
									$mytags = array_unique(array_merge($tags, $newsobj->getTags()));
									$newsobj->setTags($mytags);
									$newsobj->setLastchangeUser($_zp_current_admin_obj->getUser());
									$newsobj->save(true);
								}
								break;
							case 'clearalltags':
								$allarticles = $obj->getArticles('', 'all', true);
								foreach ($allarticles as $article) {
									$newsobj = new ZenpageNews($article['titlelink']);
									$newsobj->setTags(array());
									$newsobj->setLastchangeUser($_zp_current_admin_obj->getUser());
									$newsobj->save(true);
								}
								break;
							case 'addcats':
								$catarray = array();
								$allcats = $obj->getCategories();
								foreach ($cats as $cat) {
									$catitem = $_zp_zenpage->getCategory($cat);
									$catarray[] = $catitem['titlelink']; //to use the setCategories method we need an array with just the titlelinks!
								}
								$allcatsarray = array();
								foreach ($allcats as $allcat) {
									$allcatsarray[] = $allcat['titlelink']; //same here!
								}
								$mycats = array_unique(array_merge($catarray, $allcatsarray));
								$obj->setCategories($mycats);
								break;
							case 'clearcats':
								$obj->setCategories(array());
								break;
							case 'showall':
								$obj->set('show', 1);
								break;
							case 'hideall':
								$obj->set('show', 0);
								break;
							case 'commentson':
								$obj->set('commentson', 1);
								break;
							case 'commentsoff':
								$obj->set('commentson', 0);
								break;
							case 'resethitcounter':
								$obj->set('hitcounter', 0);
								break;
						}
						$obj->setLastchangeUser($_zp_current_admin_obj->getUser());
						$obj->save(true);
					}
				}
			}
		}
		return $action;
	}

	function zenpageBulkActionMessage($action) {
		switch ($action) {
			case 'deleteall':
				$message = gettext('Selected items deleted');
				break;
			case 'showall':
				$message = gettext('Selected items published');
				break;
			case 'hideall':
				$message = gettext('Selected items unpublished');
				break;
			case 'commentson':
				$message = gettext('Comments enabled for selected items');
				break;
			case 'commentsoff':
				$message = gettext('Comments disabled for selected items');
				break;
			case 'resethitcounter':
				$message = gettext('Hitcounter for selected items');
				break;
			case 'addtags':
				$message = gettext('Tags added to selected items');
				break;
			case 'cleartags':
				$message = gettext('Tags cleared from selected items');
				break;
			case 'alltags':
				$message = gettext('Tags added to articles of selected items');
				break;
			case 'clearalltags':
				$message = gettext('Tags cleared from articles of selected items');
				break;
			case 'addcats':
				$message = gettext('Categories added to selected items');
				break;
			case 'clearcats':
				$message = gettext('Categories cleared from selected items');
				break;
			default:
				return "<p class='notebox fade-message'>" . gettext('Nothing changed') . "</p>";
		}
		if (isset($message)) {
			return "<p class='messagebox fade-message'>" . $message . "</p>";
		}
		return false;
	}
	
	/**
	 * Creates the titlelink from the title passed.
	 * 
	 * @since 1.5.2
	 * 
	 * @param string|array $title The title respectively language array of titles
	 * @param string $date The date the article is saved
	 * @return type
	 */
	function createTitlelink($title, $date) {
		$titlelink = seoFriendly(get_language_string($title));
		if (empty($titlelink)) {
			$titlelink = seoFriendly($date);
		}
		return $titlelink;
	}

	/**
	 * Checks if a title link of this itemtype already exists
	 * 
	 * @since 1.5.2
	 * 
	 * @param string $titlelink The titlelink to check
	 * @param string $itemtype
	 * @return bool
	 */
	function checkTitlelinkDuplicate($titlelink, $itemtype) {
		global $_zp_db;
		switch ($itemtype) {
			case 'article':
				$table = $_zp_db->prefix('news');
				break;
			case 'category':
				$table = $_zp_db->prefix('news_categories');
				break;
			case 'page':
				$table = $_zp_db->prefix('pages');
				break;
		}
		$sql = 'SELECT `id` FROM ' . $table . ' WHERE `titlelink`=' . $_zp_db->quote($titlelink);
		$rslt = $_zp_db->querySingleRow($sql, false);
		return $rslt;
	}

	/**
	 * Append or prepends a date string to a titlelink as defined.
	 * Note: This does not check the item type option and will add to any string passed!
	 * 
	 * @since 1.5.2
	 * 
	 * @param string $titlelink The titleink (e.g. as created by createTitleink())
	 * @return string
	 */
	function addDateToTitlelink($titlelink) {
		$addwhere = getOption('zenpage_titlelinkdate_location');
		$dateformat = getOption('zenpage_titlelinkdate_dateformat');
		switch($dateformat) {
			case 'Y-m-d':
			case 'Ymd':
			case 'Y-m-d_H-i-s':
			case 'YmdHis':
				$date = date($dateformat);
				break;
			default:
			case 'timestamp':
				$date = time();
				break;
	}
	switch ($addwhere) {
		case 'before':
			$titlelink = $date . '-' . $titlelink;
			break;
		default:
		case 'after':
			$titlelink = $titlelink . '-' . $date;
			break;
	}
	return $titlelink;
}
