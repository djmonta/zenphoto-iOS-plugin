<?php
/*
 * userRelated plugin--tabs
 * @author Sachiko Miyamoto (djmonta)
 * @package plugins
 * @subpackage users
 */
define('OFFSET_PATH', 4);
require_once(dirname(dirname(dirname(__FILE__))) . '/zp-core/admin-globals.php');
global $_zp_current_admin_obj;

admin_securityChecks(NULL, currentRelativeURL());

$userobj = $_zp_current_admin_obj;

$msg = NULL;
if (isset($_GET['action'])) {
	$action = sanitize($_GET['action']);
	XSRFdefender($action);
	if ($action == 'profile') {
		if ($userobj->getValid()) {
			if (isset($_FILES['profile_picture_url']) && $_FILES['profile_picture_url']['error'] != UPLOAD_ERR_NO_FILE) {
				$path = saveImage($_FILES['profile_picture_url']);
				$newdata = str_replace(SERVERPATH, FULLWEBPATH, $path);
				$olddata = $userobj->get('profile_picture_url');
				$userobj->set('profile_picture_url', $newdata);
				if ($olddata != $newdata) {
					//$msg = 'applied';
					if($olddata) {
						unlink(str_replace(FULLWEBPATH, SERVERPATH, $olddata));
					}
					$updated = true;
				}
				zp_apply_filter('save_admin_custom_data', $updated, $userobj, $i, $updated);
				$userobj->save();
			} elseif (isset($_SESSION['facebook_access_token'])) {
				$plainOldArray = userRelated::facebook($_SESSION['facebook_access_token']);
				$newdata = $plainOldArray['id'];
				$olddata = $userobj->get('fb_id');
				$userobj->set('fb_id', $newdata);
				if ($olddata != $newdata) {
					$updated = true;
				}
				zp_apply_filter('save_admin_custom_data', $updated, $userobj, $i, $updated);
				$userobj->save();
			}
		}
	} elseif ($action == 'fb_disconnect') {
		if ($userobj->getValid()) {
			$newdata = NULL;
			$userobj->set('fb_id', $newdata);
			unset($_SESSION['facebook_access_token']);
			$updated = true;
			zp_apply_filter('save_admin_custom_data', $updated, $userobj, $i, $updated);
			$userobj->save();
		}
	}
	header("Location: " . FULLWEBPATH . "/" . USER_PLUGIN_FOLDER . '/userRelated/userRelatedTab.php?page=users&tab=profile&applied=' . $msg);
	exitZP();
}

function saveImage($file) {
	if (isset($file['error']) || is_int($file['error'])) {

		try {

			// $file['error'] の値を確認
			switch ($file['error']) {
				case UPLOAD_ERR_OK: // OK
					break;
				case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
				case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
					throw new RuntimeException("ファイルサイズが大きすぎます");
				default:
					throw new RuntimeException("その他のエラーが発生しました");
			}

			// $file['mime']の値はブラウザ側で偽装可能なので
			// MIMEタイプを自前でチェックする
			if (!$info = @getimagesize($file['tmp_name'])) {
				throw new RuntimeException("有効な画像ファイルを指定してください");
			}
			if (!in_array($info[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
				throw new RuntimeException("未対応の画像形式です");
			}

			// 画像処理に使う関数名を決定する
			$create = str_replace('/', 'createfrom', $info['mime']);
			$output = str_replace('/', '', $info['mime']);

			// 縦横比を維持したまま 120 * 120 以下に収まるサイズを求める
			if ($info[0] >= $info[1]) {
				$dst_w = 120;
				$dst_h = ceil(120 * $info[1] / max($info[0], 1));
			} else {
				$dst_w = ceil(120 * $info[0] / max($info[1], 1));
				$dst_h = 120;
			}

			// 元画像リソースを生成する
			if (!$src = @$create($file['tmp_name'])) {
				throw new RuntimeException("画像リソースの生成に失敗しました");
			}

			// リサンプリング先画像リソースを生成する
			$dst = imagecreatetruecolor($dst_w, $dst_h);

			// getimagesize関数で得られた情報も利用してリサンプリングを行う
			imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_w, $dst_h, $info[0], $info[1]);

			$filename = sprintf('%s%s', sha1_file($file['tmp_name']), image_type_to_extension($info[2]));
			$filename = SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/userRelated/profile_images/' . $filename;
			// ファイルデータからSHA-1ハッシュを取ってファイル名を決定し、保存する
			if (!$output($dst, $filename)) {
				throw new RuntimeException("ファイル保存時にエラーが発生しました");
			}
				$msgs[] = ['green', "リサイズして保存しました"];

		} catch (RuntimeException $e) {
			$msgs[] = ['red', $e->getMessage()];
			debugLog($e->getMessage());
		}

		// リソースを解放
		if (isset($msg) && is_resource($img)) {
			imagedestroy($img);
		}
		if (isset($dst) && is_resource($dst)) {
			imagedestroy($dst);
		}
		return $filename;
	} else {
		debugLog('ERROR: something!');
	}
}

printAdminHeader('users');
?>
<?php
echo '</head>' . "\n";
?>

<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php
			if (isset($_GET['applied'])) {
				$msg = sanitize($_GET['applied']);
				if ($msg) {
					echo "<div class=\"errorbox space\">";
					echo "<h2>" . $msg . "</h2>";
					echo "</div>";
				} else {
					echo '<div class="messagebox fade-message">';
					echo "<h2>" . gettext('Processed') . "</h2>";
					echo '</div>';
				}
			}
			$subtab = printSubtabs();
			?>
			<div id="tab_users" class="tabbox">
				<p><?php echo gettext("Edit your profile."); ?>
				<form action="?action=profile" class="dirty-check" method="post" autocomplete="off" enctype="multipart/form-data">
					<?php XSRFToken('profile'); ?>
					<span class="buttons">
						<button type="submit"><img src="<?php echo FULLWEBPATH . "/" . ZENFOLDER; ?>/images/pass.png" alt="" /><strong><?php echo gettext("Apply"); ?></strong></button>
						<button type="reset"><img src="<?php echo FULLWEBPATH . "/" . ZENFOLDER; ?>/images/reset.png" alt="" /><strong><?php echo gettext("Reset"); ?></strong></button>
					</span>
					<br class="clearall" />
					<table class="bordered">
						<tr>
							<td>Profile Picture</td>
							<td>
								<input type="file" size="40" name="profile_picture_url" id="profile_picture_url"/>
								<?php if ($userobj->get('profile_picture_url')) { ?>
									<img src="<?php echo html_encode($userobj->get('profile_picture_url')); ?>" style="width: 120px; height: auto;" />
								<?php } ?>
							</td>
						</tr>
					</table>
				</form>
				<?php if(getOption('fb_integration')) { ?>
					<form action="?action=fb_disconnect" class="dirty-check" method="post" autocomplete="off">
					 <?php XSRFToken('fb_disconnect'); ?>
						<table class="bordered">
							<tr>
								<td>Facebook Setting</td>
								<td>
									<?php
									$loginUrl = userRelated::facebookLoginUrl();
									echo '<a href="' . $loginUrl . '">Connect with Facebook</a>';
									if ($_SESSION['facebook_access_token']) {
										$plainOldArray = userRelated::facebook($_SESSION['facebook_access_token']);
										echo 'Logged in as ' . $plainOldArray['name'];
									?>
									<button type="submit">Disonnect</button>
									<?php } ?>
								</td>
							</tr>
						</table>
					</form>
				<?php } ?>
				<br class="clearall" />
			</div>

		</div>
	</div>
</body>
</html>
