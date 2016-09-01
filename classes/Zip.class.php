<?php
class Zip {
	const BUFFER_SIZE = 4194304; // 4MiB
	private $currentOffset;
	private $entries;
	private $tempfile;
	
	public function open($filename,$tempname=null) {
		set_time_limit(0);
		if ($tempname == null) {
			header('Pragma: no-cache');
			header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
			header('Cache-Control: no-store');
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.rawurlencode($filename));
			
			$this->tempfile = null;
		} else {
			$this->tempfile = fopen($tempname,'wb');
		}
		$this->currentOffset = 0;
		$this->entries = array();
	}
	
	public function addEmptyDir($dirname) {
		if ($this->addFile('php://temp', $dirname.'/') === false) {
			return false;
		}
	}
	
	public function addFromString($localname, $contents) {
		$tmp = tempnam(sys_get_temp_dir(), __CLASS__);
		$pointer = @fopen($tmp, 'wb');
		if ($pointer === false) {
			@unlink($tmp);
			return false;
		}
		fwrite($pointer, $contents);
		$result = $this->addFile($tmp,$localname);
		fclose($pointer);
		@unlink($tmp);
		if ($result === false) {
			return false;
		}
	}
	
	public function addFile($filename, $localname = null) {
		$entry = new ZipEntry(empty($localname) ? basename($filename) : $localname, $this->currentOffset);
		if ($entry->open($filename) === false) {
			return false;
		}
		$this->entries[] = $entry;
		ob_start();
		$this->write(0x504B0304, 'N'); // sig entry
		$this->writeEntryStat($entry);
		$this->writeBuffer($entry->name);
		if ($this->tempfile == null) $this->currentOffset += strlen(ob_get_flush());
		while (!feof($entry->pointer)) {
			$buffer = @fread($entry->pointer, self::BUFFER_SIZE);
			$this->writeBuffer($buffer);
			flush();
			if ($this->tempfile == null) $this->currentOffset += strlen($buffer);
		}
		$entry->close();
	}
	
	public function close() {
		$currentOffset = $this->currentOffset;
		
		ob_start();
		foreach ($this->entries as $entry) {
			$this->write(0x504B0102, 'N'); // sig index
			$this->write(0); // os: fat
			$this->writeEntryStat($entry);
			$this->write(0); // comment len
			$this->write(0); // disk # start
			$this->write(0); // internal attr
			$this->write(0, 'V'); // external attr
			$this->write($entry->offset, 'V');
			$this->writeBuffer($entry->name);
		}
		
		if ($this->tempfile == null) $length = strlen(ob_get_flush());
		else $length = $this->currentOffset - $currentOffset;
		
		$this->write(0x504B0506, 'N'); // sig end
		$this->write(0); // disk number
		$this->write(0); // disk # index start
		$this->write(count($this->entries)); // disk entries
		$this->write(count($this->entries)); // total entries
		$this->write($length, 'V');
		$this->write($currentOffset, 'V');
		$this->write(0); // comment len
		flush();
		
		if ($this->tempfile != null) fclose($this->tempfile);
	}
	
	private function writeEntryStat($entry) {
		$this->write(substr($entry->name, -1) == '/' ? 20 : 10);
		$this->write(2048); // flags: unicode filename
		$this->write(0); // compression: store
		$this->write($entry->mtime, 'V');
		$this->write($entry->crc, 'V');
		$this->write($entry->size, 'V'); // compressed size
		$this->write($entry->size, 'V'); // uncompressed size
		$this->write(strlen($entry->name));
		$this->write(0); // extra field len
	}
	
	private function write($binary, $format = 'v') {
		$this->writeBuffer(pack($format, $binary));
	}
	
	private function writeBuffer($data) {
		if ($this->tempfile == null) echo $data;
		else $this->currentOffset+= fwrite($this->tempfile,$data);
	}
}

class ZipEntry {
	public $offset;
	public $pointer;
	public $name;
	public $crc;
	public $size;
	public $mtime;
	public function __construct($name, $offset) {
		$this->offset = $offset;
		$this->name = $name;
	}
	public function open($filename) {
		$this->pointer = @fopen($filename, 'rb');
		if ($this->pointer === false) {
			return false;
		}
		list(, $this->crc) = unpack('N', hash_file('crc32b', $filename, true));
		$fstat = fstat($this->pointer);
		$this->size = $fstat['size'];
		$mtime = $filename == 'php://temp' ? time() : $fstat['mtime'];
		$this->mtime = (date('Y', $mtime) - 1980) << 25 | date('m', $mtime) << 21 | date('d', $mtime) << 16 |
			date('H', $mtime) << 11 | date('i', $mtime) << 5 | date('s', $mtime) >> 1;
	}
	public function close() {
		fclose($this->pointer);
	}
}
?>