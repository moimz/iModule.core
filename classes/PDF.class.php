<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * TCPDF 라이브러리를 확장하여 PDF 파일을 생성한다.
 * 
 * @file /classes/PDF.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 12. 21.
 */

global $IM;

/** defines the site-specific location of fonts */
define('PDF_CUSTOM_FONT_PATH', __IM_PATH__.'/TCPDF/fonts/');

/** default font to be used if there are more of them available */
define('PDF_DEFAULT_FONT', 'FreeSerif');

/** tell tcpdf it is configured here instead of in its own config file */
define('K_TCPDF_EXTERNAL_CONFIG', 1);

// The configuration constants needed by tcpdf follow

/** tcpdf installation path */
define('K_PATH_MAIN', __IM_PATH__.'/classes/TCPDF/');

/** URL path to tcpdf installation folder */
define('K_PATH_URL', __IM_DIR__.'/classes/TCPDF/');

/** path for PDF fonts */
define('K_PATH_FONTS', K_PATH_MAIN.'fonts/');

/** cache directory for temporary files (full path) */
define('K_PATH_CACHE', $IM->getModule('attachment')->getTempPath(true).'/');

/** images directory */
define('K_PATH_IMAGES', __IM_PATH__.'/');

/** blank image */
define('K_BLANK_IMAGE', K_PATH_MAIN.'/images/spacer.gif');

/** height of cell repect font height */
define('K_CELL_HEIGHT_RATIO', 1.25);

/** reduction factor for small font */
define('K_SMALL_RATIO', 2/3);

require_once(__IM_PATH__.'/classes/TCPDF/tcpdf.php');

class PDF extends TCPDF {
	/**
	 * Class constructor
	 *
	 * See the parent class documentation for the parameters info.
	 */
	public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8') {
		global $IM;
		
		parent::__construct($orientation, $unit, $format, $unicode, $encoding);

		if (is_dir(PDF_CUSTOM_FONT_PATH)) {
			$fontfiles = $this->_getfontfiles(PDF_CUSTOM_FONT_PATH);

			if (count($fontfiles) == 1) {
				$autofontname = substr($fontfiles[0], 0, -4);
				$this->AddFont($autofontname, '', $autofontname.'.php');
				$this->SetFont($autofontname);
			} else if (count($fontfiles == 0)) {
				$this->SetFont(PDF_DEFAULT_FONT);
			}
		} else {
			$this->SetFont(PDF_DEFAULT_FONT);
		}

		// theses replace the tcpdf's config/lang/ definitions
		$this->l['w_page']		  = '';//get_string('page');
		$this->l['a_meta_language'] = $IM->getLanguage();
		$this->l['a_meta_charset']  = 'UTF-8';
		$this->l['a_meta_dir']	  = '';//get_string('thisdirection', 'langconfig');
	}

	/**
	 * Send the document to a given destination: string, local file or browser.
	 * In the last case, the plug-in may be used (if present) or a download ("Save as" dialog box) may be forced.<br />
	 * The method first calls Close() if necessary to terminate the document.
	 * @param $name (string) The name of the file when saved. Note that special characters are removed and blanks characters are replaced with the underscore character.
	 * @param $dest (string) Destination where to send the document. It can take one of the following values:<ul><li>I: send the file inline to the browser (default). The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.</li><li>D: send to the browser and force a file download with the name given by name.</li><li>F: save to a local server file with the name given by name.</li><li>S: return the document as a string (name is ignored).</li><li>FI: equivalent to F + I option</li><li>FD: equivalent to F + D option</li><li>E: return the document as base64 mime multi-part email attachment (RFC 2045)</li></ul>
	 * @public
	 * @since 1.0
	 * @see Close()
	 */
	public function Output($name='doc.pdf', $dest='I') {
		$olddebug = error_reporting(0);
		$result  = parent::output($name, $dest);
		error_reporting($olddebug);
		return $result;
	}

	/**
	 * Return fonts path
	 * Overriding TCPDF::_getfontpath()
	 *
	 * @global object
	 */
	protected function _getfontpath() {
		if (is_dir(PDF_CUSTOM_FONT_PATH)
					&& count($this->_getfontfiles(PDF_CUSTOM_FONT_PATH)) > 0) {
			$fontpath = PDF_CUSTOM_FONT_PATH;
		} else {
			$fontpath = K_PATH_FONTS;
		}
		return $fontpath;
	}

	/**
	 * Get the .php files for the fonts
	 */
	protected function _getfontfiles($fontdir) {
		echo $fontdir;
		$dirlist = get_directory_list($fontdir);
		$fontfiles = array();

		foreach ($dirlist as $file) {
			if (substr($file, -4) == '.php') {
				array_push($fontfiles, $file);
			}
		}
		return $fontfiles;
	}
}
?>