<?php

namespace BOMRadar;

/**
 * Download and reproduce the Australian BOM radars onto your site or application
 *
 * @package BOMRadar
 * @author Josh Marshall <josh@jmarshall.com.au>
 */
class Radar {

	private $radar_id = NULL;
	private $host = NULL;
	private $port = NULL;
	private $timeout = NULL;
	private $passive = NULL;

	public function __construct($radar_id, $host = 'ftp.bom.gov.au', $port = 21, $timeout = 90, $passive = true) {
		$this->radar_id = $radar_id;
		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;
		$this->passive = $passive;
	}

	/**
	 * Sync the radar files from BOM to the local site
	 * Suggest to call this approx every 10 minutes
	 *
	 * @param string $directory the full path to the local directory to store the files
	 * @param integer $clean_up_hours number of hours to keep the files, or 0 for forever
	 */
	public function sync($directory, $clean_up_hours = 2) {
		$directory .= '/IDR' . $this->radar_id;
		if (!file_exists($directory)) {
			mkdir($directory, 0755, true);
		}
		$connection = new \Touki\FTP\Connection\AnonymousConnection($this->host, $this->port, $this->timeout, $this->passive);
		$connection->open();

		$factory = new \Touki\FTP\FTPFactory;
		$ftp = $factory->build($connection);

		// Sync background images
		$files_to_sync = array(
			'IDR' . $this->radar_id . '.background.png',
			'IDR' . $this->radar_id . '.locations.png',
			'IDR' . $this->radar_id . '.range.png',
			'IDR' . $this->radar_id . '.topography.png',
		);

		foreach ($files_to_sync as $file_to_sync) {
			if (file_exists($directory . '/' . $file_to_sync)) {
				continue;
			}
			$file = $ftp->findFileByName('/anon/gen/radar_transparencies/' . $file_to_sync);

			if (!$file) {
				return;
			}

			$ftp->download($directory . '/' . $file_to_sync, $file);
		}

		// Sync today's images

		$radar_images = new \Touki\FTP\Model\Directory('/anon/gen/radar/');
		$imgfiles = $ftp->findFiles($radar_images);
		foreach ($imgfiles as $imgfile) {
			$filepath = $imgfile->getRealpath();
			if (strpos($filepath, 'IDR' . $this->radar_id . '.T') === FALSE) {
				continue;
			}
			$filename = basename($filepath);

			if (file_exists($directory . '/' . $filename)) {
				continue;
			}

			$ftp->download($directory . '/' . $filename, $imgfile);
		}

		if (!$clean_up_hours) {
			return;
		}

		// Find all files
		$files_to_clean = glob($directory . '/IDR' . $this->radar_id . '.T*');
		$now = new \DateTime('NOW');
		foreach ($files_to_clean as $file_to_clean) {
			// parse date string

			$date = \DateTime::createFromFormat('YmdHi', substr(basename($file_to_clean), strlen('IDR' . $this->radar_id . '.T.'), 12));
			$age_in_hours = $date->diff($now)->h;
			if ($age_in_hours > $clean_up_hours) {
				unlink($file_to_clean);
			}
		}
	}

	/**
	 * Render an example output to HTML and javascript
	 *
	 * @param string $directory the full path to the local directory to store the files
	 * @param string $url the relative url path to the folder containing the files
	 * @param integer $number_in_loop the number of most recent files to show in the radar animation
	 * @return string HTML code
	 */
	public function render($directory, $url, $number_in_loop = 6) {
		ob_start();
		$directory .= '/IDR' . $this->radar_id;
		$url .= '/IDR' . $this->radar_id;
		$uniq = uniqid('radar');
		$files = glob($directory . '/IDR' . $this->radar_id . '.T*');
		arsort($files);
		$imagefiles = array_splice($files, 0, $number_in_loop);
		sort($imagefiles);
		?>
		<div class="radar <?= $uniq ?>">
			<img class="radarbg" src="<?= $url ?>/IDR<?= $this->radar_id ?>.background.png" />
			<img class="radaroverlay" src="<?= $url ?>/IDR<?= $this->radar_id ?>.topography.png" />
			<img class="radaroverlay" src="<?= $url ?>/IDR<?= $this->radar_id ?>.locations.png" />
			<img class="radaroverlay" src="<?= $url ?>/IDR<?= $this->radar_id ?>.range.png" />
			<?php foreach ($imagefiles as $imagefile) { ?>
				<img class="radaroverlay <?= $uniq ?>img hidden" src="<?= $url ?>/<?= basename($imagefile) ?>" />
			<?php } ?>

		</div>
		<style>
			.<?= $uniq ?> {
				position: relative;
			}
			.<?= $uniq ?> .radaroverlay {
				position: absolute;
				top: 0px;
				left: 0px;
			}
			.hidden {
				display: none;
			}
		</style>
		<script type="text/javascript">
			function <?= $uniq ?>_loop(item) {
				var radarlist = document.getElementsByClassName('<?= $uniq ?>img');
				for (var i = 0; i < radarlist.length; ++i) {
					radarlist[i].classList.add('hidden');
				}
				if (item >= radarlist.length) {
					item = 0;
				}
				radarlist[item].classList.remove('hidden');
				setTimeout(function () {<?= $uniq ?>_loop(++item);
				}, 1000);
			}
		<?= $uniq ?>_loop(0);
		</script>
		<?php
		return ob_get_clean();
	}

}
