<?php
/**
 * 이 파일은 MoimzTools 의 일부입니다. (https://www.moimz.com)
 *
 * 두개의 텍스트의 차이점을 비교한다.
 *
 * @file /classes/Diff.class.php
 * @author Arzz
 * @license MIT License
 * @version 1.0.0
 * @modified 2020. 2. 24.
 */
class Diff {
	private $left = null;
	private $right = null;
	private $leftSequence = null;
	private $rightSequence = null;
	private $groupedCodes = null;
	private $defaultOptions = array(
		'context'=>3,
		'ignoreNewLines'=>false,
		'ignoreWhitespace'=>false,
		'ignoreCase'=>false,
		'tabSize'=>4
	);
	private $options = array();

	public function __construct($left,$right,$options=array()) {
		$this->left = explode("\n",preg_replace("/(\r\n|\n)/","\n",$left));
		$this->right = explode("\n",preg_replace("/(\r\n|\n)/","\n",$right));

		if (is_array($options)) $this->options = array_merge($this->defaultOptions,$options);
		else $this->options = $this->defaultOptions;
	}

	private function mb_substr_replace($string,$replacement,$start,$length=null,$encoding=null) {
		$string_length = (is_null($encoding) === true) ? mb_strlen($string) : mb_strlen($string,$encoding);
		
		if ($start < 0) {
			$start = max(0,$string_length + $start);
		} elseif ($start > $string_length) {
			$start = $string_length;
		}
		
		if ($length < 0) {
			$length = max(0,$string_length - $start + $length);
		} elseif ((is_null($length) === true) || ($length > $string_length)) {
			$length = $string_length;
		}
		
		if (($start + $length) > $string_length) {
			$length = $string_length - $start;
		}
		
		if (is_null($encoding) === true) return mb_substr($string,0,$start).$replacement.mb_substr($string,$start + $length,$string_length - $start - $length);
		return mb_substr($string,0,$start,$encoding).$replacement.mb_substr($string,$start + $length,$string_length - $start - $length,$encoding);
	}
	
	private function getChanges() {
		$left = $this->getLeft();
		$right = $this->getRight();
		
		$changes = array();
		$opCodes = $this->getGroupedOpcodes();
		
		foreach ($opCodes as $group) {
			$blocks = array();
			$lastTag = null;
			$lastBlock = 0;
			foreach ($group as $code) {
				list($tag, $i1, $i2, $j1, $j2) = $code;

				if ($tag == 'replace' && $i2 - $i1 == $j2 - $j1) {
					for ($i = 0; $i < ($i2 - $i1); ++$i) {
						$fromLine = $left[$i1 + $i];
						$toLine = $right[$j1 + $i];

						list($start, $end) = $this->getChangeExtent($fromLine, $toLine);
						if ($start != 0 || $end != 0) {
							$last = $end + mb_strlen($fromLine,'utf-8');
							$fromLine = $this->mb_substr_replace($fromLine, "\0", $start, 0);
							$fromLine = $this->mb_substr_replace($fromLine, "\1", $last + 1, 0);
							$last = $end + mb_strlen($toLine,'utf-8');
							$toLine = $this->mb_substr_replace($toLine, "\0", $start, 0);
							$toLine = $this->mb_substr_replace($toLine, "\1", $last + 1, 0);
							$left[$i1 + $i] = $fromLine;
							$right[$j1 + $i] = $toLine;
						}
					}
				}

				if ($tag != $lastTag) {
					$blocks[] = array(
						'tag'=>$tag,
						'base'=>array(
							'offset'=>$i1,
							'lines'=>array()
						),
						'changed'=>array(
							'offset'=>$j1,
							'lines'=>array()
						)
					);
					$lastBlock = count($blocks)-1;
				}

				$lastTag = $tag;

				if ($tag == 'equal') {
					$lines = array_slice($left, $i1, ($i2 - $i1));
					$blocks[$lastBlock]['base']['lines'] += $this->formatLines($lines);
					$lines = array_slice($right, $j1, ($j2 - $j1));
					$blocks[$lastBlock]['changed']['lines'] +=  $this->formatLines($lines);
				} else {
					if ($tag == 'replace' || $tag == 'delete') {
						$lines = array_slice($left, $i1, ($i2 - $i1));
						$lines = $this->formatLines($lines);
						$lines = str_replace(array("\0", "\1"), array('<del>', '</del>'), $lines);
						$blocks[$lastBlock]['base']['lines'] += $lines;
					}

					if ($tag == 'replace' || $tag == 'insert') {
						$lines = array_slice($right, $j1, ($j2 - $j1));
						$lines = $this->formatLines($lines);
						$lines = str_replace(array("\0", "\1"), array('<ins>', '</ins>'), $lines);
						$blocks[$lastBlock]['changed']['lines'] += $lines;
					}
				}
			}
			
			$changes[] = $blocks;
		}
		
		return $changes;
	}
	
	public function render($title=null) {
		$changes = $this->getChanges();
		
		$html = '<div data-role="diff" data-mode="inline">';
		$html.= '<h1>';
		if ($title) $html.= $title;
		$html.= '<div data-role="button">';
		$html.= '<button type="button" onclick="$(\'div[data-role=diff]\').attr(\'data-mode\',\'inline\');">';
		$html.= '<svg aria-label="file" class="octicon octicon-file" viewBox="0 0 12 16" version="1.1" width="12" height="16" role="img"><path fill-rule="evenodd" d="M6 5H2V4h4v1zM2 8h7V7H2v1zm0 2h7V9H2v1zm0 2h7v-1H2v1zm10-7.5V14c0 .55-.45 1-1 1H1c-.55 0-1-.45-1-1V2c0-.55.45-1 1-1h7.5L12 4.5zM11 5L8 2H1v12h10V5z"></path></svg>';
		$html.= '</button>';
		$html.= '<button type="button" onclick="$(\'div[data-role=diff]\').attr(\'data-mode\',\'side\');">';
		$html.= '<svg class="octicon octicon-book" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M3 5h4v1H3V5zm0 3h4V7H3v1zm0 2h4V9H3v1zm11-5h-4v1h4V5zm0 2h-4v1h4V7zm0 2h-4v1h4V9zm2-6v9c0 .55-.45 1-1 1H9.5l-1 1-1-1H2c-.55 0-1-.45-1-1V3c0-.55.45-1 1-1h5.5l1 1 1-1H15c.55 0 1 .45 1 1zm-8 .5L7.5 3H2v9h6V3.5zm7-.5H9.5l-.5.5V12h6V3z"></path></svg>';
		$html.= '</button>';
		$html.= '</div>';
		$html.= '</h1>';
		if (empty($changes) == true) return $html;
		
		$html.= '<table>';
		foreach ($changes as $i=>$blocks) {
			if ($i > 0) {
				$html.= '<tbody class="skipped">';
				$html.= '<tr>';
				$html.= '<th data-role="left">&hellip;</th>';
				$html.= '<td data-role="left"></td>';
				$html.= '<th data-role="right">&hellip;</th>';
				$html.= '<td data-role="right"></td>';
				$html.= '<td data-role="inline"></td>';
				$html.= '<td>&nbsp;</td>';
				$html.= '</tr>';
				$html.= '</tbody>';
			}
			
			foreach ($blocks as $change) {
				$html.= '<tbody class="changed">';
				if ($change['tag'] == 'equal') {
					foreach ($change['base']['lines'] as $no=>$line) {
						$fromLine = $change['base']['offset'] + $no + 1;
						$toLine = $change['changed']['offset'] + $no + 1;
						$html.= '<tr>';
						$html.= '<th data-role="left"><span data-line-number="'.$fromLine.'"></span></th>';
						$html.= '<td data-role="left"><span>'.$line.'</span></td>';
						$html.= '<th data-role="right"><span data-line-number="'.$toLine.'"></span></th>';
						$html.= '<td data-role="right"><span>'.$line.'</span></td>';
						$html.= '<td data-role="inline">'.$line.'</td>';
						$html.= '</tr>';
					}
				} elseif ($change['tag'] == 'insert') {
					foreach ($change['changed']['lines'] as $no=>$line) {
						$toLine = $change['changed']['offset'] + $no + 1;
						$html.= '<tr class="insert">';
						$html.= '<th data-role="left"></th>';
						$html.= '<td data-role="left"></td>';
						$html.= '<th data-role="right"><span data-line-number="'.$toLine.'"></span></th>';
						$html.= '<td data-role="right"><ins>'.$line.'</ins></td>';
						$html.= '<td data-role="inline"><ins>'.$line.'</ins></td>';
						$html.= '</tr>';
					}
				} else if ($change['tag'] == 'delete') {
					foreach ($change['base']['lines'] as $no=>$line) {
						$fromLine = $change['base']['offset'] + $no + 1;
						$html.= '<tr class="delete">';
						$html.= '<th data-role="left"><span data-line-number="'.$fromLine.'"></span></th>';
						$html.= '<td data-role="left"><del>'.$line.'</del></td>';
						$html.= '<th data-role="right"></th>';
						$html.= '<td data-role="right"></td>';
						$html.= '<td data-role="inline"><del>'.$line.'</del></td>';
						$html.= '</tr>';
					}
				} else if ($change['tag'] == 'replace') {
					foreach ($change['base']['lines'] as $no=>$line) {
						$fromLine = $change['base']['offset'] + $no + 1;
						$html.= '<tr data-role="inline" class="delete">';
						$html.= '<th data-role="left"><span data-line-number="'.$fromLine.'"></span></th>';
						$html.= '<td data-role="left"></td>';
						$html.= '<th data-role="right"></th>';
						$html.= '<td data-role="right"></td>';
						$html.= '<td data-role="inline"><span>'.$line.'</span></td>';
						$html.= '</tr>';
					}

					foreach ($change['changed']['lines'] as $no=>$line) {
						$toLine = $change['changed']['offset'] + $no + 1;
						$html.= '<tr data-role="inline" class="insert">';
						$html.= '<th data-role="left"></th>';
						$html.= '<td data-role="left"></td>';
						$html.= '<th data-role="right"><span data-line-number="'.$toLine.'"></span></th>';
						$html.= '<td data-role="right"></td>';
						$html.= '<td data-role="inline"><span>'.$line.'</span></td>';
						$html.= '</tr>';
					}
					
					if (count($change['base']['lines']) >= count($change['changed']['lines'])) {
						foreach ($change['base']['lines'] as $no=>$line) {
							$fromLine = $change['base']['offset'] + $no + 1;
							$html.= '<tr data-role="side" class="replace">';
							$html.= '<th data-role="left"><span data-line-number="'.$fromLine.'"></span></th>';
							$html.= '<td data-role="left"><span>'.$line.'</span></td>';
							if (!isset($change['changed']['lines'][$no])) {
								$toLine = '';
								$changedLine = '';
							} else {
								$toLine = $change['base']['offset'] + $no + 1;
								$changedLine = '<span>'.$change['changed']['lines'][$no].'</span>';
							}
							$html.= '<th data-role="right"><span data-line-number="'.$toLine.'"></span></th>';
							$html.= '<td data-role="right">'.$changedLine.'</td>';
							$html.= '<td data-role="inline"></td>';
							$html.= '</tr>';
						}
					} else {
						foreach ($change['changed']['lines'] as $no=>$changedLine) {
							if (!isset($change['base']['lines'][$no])) {
								$fromLine = '';
								$line = '';
							} else {
								$fromLine = $change['base']['offset'] + $no + 1;
								$line = '<span>'.$change['base']['lines'][$no].'</span>';
							}
							$html.= '<tr data-role="side" class="replace">';
							$html.= '<th data-role="left"><span data-line-number="'.$fromLine.'"></span></th>';
							$html.= '<td data-role="left"><span>'.$line.'</span></td>';
							$toLine = $change['changed']['offset'] + $no + 1;
							$html.= '<th data-role="right"><span data-line-number="'.$toLine.'"></span></th>';
							$html.= '<td data-role="right">'.$changedLine.'</td>';
							$html.= '<td data-role="inline"></td>';
							$html.= '</tr>';
						}
					}
				}
				$html.= '</tbody>';
			}
		}
		$html.= '</table>';
		$html.= '</div>';
		
		return $html;
	}
	
	public function getLeft($start=0, $end=null) {
		if ($start == 0 && $end === null) return $this->left;
		
		if ($end === null) $length = 1;
		else $length = $end - $start;

		return array_slice($this->left,$start,$length);
	}
	
	public function getRight($start=0, $end=null) {
		if ($start == 0 && $end === null) return $this->right;

		if ($end === null) $length = 1;
		else $length = $end - $start;

		return array_slice($this->right,$start,$length);
	}
	
	public function setLeftSequence($leftSequence) {
		if (!is_array($leftSequence)) $leftSequence = str_split($leftSequence);
		if ($leftSequence == $this->leftSequence) return;
		
		$this->leftSequence= $leftSequence;
		$this->matchingBlocks = null;
		$this->opCodes = null;
	}
	
	public function setRightSequence($rightSequence) {
		if (!is_array($rightSequence)) $rightSequence = str_split($rightSequence);
		if ($rightSequence == $this->rightSequence) return;

		$this->rightSequence = $rightSequence;
		$this->matchingBlocks = null;
		$this->opCodes = null;
		$this->fullBCount = null;
		
		$length = count($this->rightSequence);
		$this->b2j = array();
		$popularDict = array();

		for ($i=0;$i<$length;++$i) {
			$char = $this->rightSequence[$i];
			if (isset($this->b2j[$char]) == true) {
				if ($length >= 200 && count($this->b2j[$char]) * 100 > $length) {
					$popularDict[$char] = 1;
					unset($this->b2j[$char]);
				} else {
					$this->b2j[$char][] = $i;
				}
			} else {
				$this->b2j[$char] = array($i);
			}
		}

		foreach (array_keys($popularDict) as $char) {
			unset($this->b2j[$char]);
		}
	}
	
	private function arrayGetDefault($array,$key,$default) {
		if (isset($array[$key])) return $array[$key];
		else return $default;
	}
	
	private function isBJunk($rightSequence) {
		if (isset($this->juncDict[$rightSequence])) return true;
		return false;
	}
	
	public function linesAreDifferent($aIndex, $bIndex) {
		$lineA = $this->leftSequence[$aIndex];
		$lineB = $this->rightSequence[$bIndex];

		if ($this->options['ignoreWhitespace']) {
			$replace = array("\t", ' ');
			$lineA = str_replace($replace, '', $lineA);
			$lineB = str_replace($replace, '', $lineB);
		}

		if ($this->options['ignoreCase']) {
			$lineA = strtolower($lineA);
			$lineB = strtolower($lineB);
		}

		if ($lineA != $lineB) return true;
		return false;
	}
	
	public function findLongestMatch($alo,$ahi,$blo,$bhi) {
		$leftSequence = $this->leftSequence;
		$rightSequence = $this->rightSequence;

		$bestI = $alo;
		$bestJ = $blo;
		$bestSize = 0;

		$j2Len = array();
		$nothing = array();

		for ($i=$alo;$i<$ahi;++$i) {
			$newJ2Len = array();
			$jDict = $this->arrayGetDefault($this->b2j, $leftSequence[$i], $nothing);
			foreach ($jDict as $jKey=>$j) {
				if ($j < $blo) continue;
				if ($j >= $bhi) break;

				$k = $this->arrayGetDefault($j2Len, $j -1, 0) + 1;
				$newJ2Len[$j] = $k;
				if ($k > $bestSize) {
					$bestI = $i - $k + 1;
					$bestJ = $j - $k + 1;
					$bestSize = $k;
				}
			}

			$j2Len = $newJ2Len;
		}

		while ($bestI > $alo && $bestJ > $blo && !$this->isBJunk($rightSequence[$bestJ - 1]) &&
			!$this->linesAreDifferent($bestI - 1, $bestJ - 1)) {
				--$bestI;
				--$bestJ;
				++$bestSize;
		}

		while ($bestI + $bestSize < $ahi && ($bestJ + $bestSize) < $bhi &&
			!$this->isBJunk($rightSequence[$bestJ + $bestSize]) && !$this->linesAreDifferent($bestI + $bestSize, $bestJ + $bestSize)) {
				++$bestSize;
		}

		while ($bestI > $alo && $bestJ > $blo && $this->isBJunk($rightSequence[$bestJ - 1]) &&
			!$this->isLineDifferent($bestI - 1, $bestJ - 1)) {
				--$bestI;
				--$bestJ;
				++$bestSize;
		}

		while ($bestI + $bestSize < $ahi && $bestJ + $bestSize < $bhi &&
			$this->isBJunk($rightSequence[$bestJ + $bestSize]) && !$this->linesAreDifferent($bestI + $bestSize, $bestJ + $bestSize)) {
				++$bestSize;
		}

		return array($bestI,$bestJ,$bestSize);
	}
	
	public function getMatchingBlocks() {
		if (!empty($this->matchingBlocks)) return $this->matchingBlocks;

		$aLength = count($this->leftSequence);
		$bLength = count($this->rightSequence);

		$queue = array(
			array(
				0,
				$aLength,
				0,
				$bLength
			)
		);

		$matchingBlocks = array();
		while (!empty($queue)) {
			list($alo, $ahi, $blo, $bhi) = array_pop($queue);
			$x = $this->findLongestMatch($alo, $ahi, $blo, $bhi);
			list($i, $j, $k) = $x;
			if ($k) {
				$matchingBlocks[] = $x;
				if ($alo < $i && $blo < $j) {
					$queue[] = array(
						$alo,
						$i,
						$blo,
						$j
					);
				}

				if ($i + $k < $ahi && $j + $k < $bhi) {
					$queue[] = array(
						$i + $k,
						$ahi,
						$j + $k,
						$bhi
					);
				}
			}
		}

		usort($matchingBlocks, array($this, 'tupleSort'));

		$i1 = 0;
		$j1 = 0;
		$k1 = 0;
		$nonAdjacent = array();
		foreach ($matchingBlocks as $block) {
			list($i2, $j2, $k2) = $block;
			if ($i1 + $k1 == $i2 && $j1 + $k1 == $j2) {
				$k1 += $k2;
			} else {
				if ($k1) {
					$nonAdjacent[] = array(
						$i1,
						$j1,
						$k1
					);
				}

				$i1 = $i2;
				$j1 = $j2;
				$k1 = $k2;
			}
		}

		if ($k1) {
			$nonAdjacent[] = array(
				$i1,
				$j1,
				$k1
			);
		}

		$nonAdjacent[] = array(
			$aLength,
			$bLength,
			0
		);

		$this->matchingBlocks = $nonAdjacent;
		return $this->matchingBlocks;
	}
	
	public function getOpCodes() {
		if (!empty($this->opCodes)) return $this->opCodes;

		$i = 0;
		$j = 0;
		$this->opCodes = array();

		$blocks = $this->getMatchingBlocks();
		foreach ($blocks as $block) {
			list($ai, $bj, $size) = $block;
			$tag = '';
			if ($i < $ai && $j < $bj) {
				$tag = 'replace';
			} elseif ($i < $ai) {
				$tag = 'delete';
			} elseif ($j < $bj) {
				$tag = 'insert';
			}

			if ($tag) {
				$this->opCodes[] = array(
					$tag,
					$i,
					$ai,
					$j,
					$bj
				);
			}

			$i = $ai + $size;
			$j = $bj + $size;

			if ($size) {
				$this->opCodes[] = array(
					'equal',
					$ai,
					$i,
					$bj,
					$j
				);
			}
		}
		return $this->opCodes;
	}
	
	public function getGroupedOpcodes($context=3) {
		if (!is_null($this->groupedCodes)) return $this->groupedCodes;
		
		$context = $this->options['context'];
		
		$this->setLeftSequence($this->left);
		$this->setRightSequence($this->right);
		
		$opCodes = $this->getOpCodes();
		if (empty($opCodes)) {
			$opCodes = array(
				array(
					'equal',
					0,
					1,
					0,
					1
				)
			);
		}

		if ($opCodes[0][0] == 'equal') {
			$opCodes[0] = array(
				$opCodes[0][0],
				max($opCodes[0][1], $opCodes[0][2] - $context),
				$opCodes[0][2],
				max($opCodes[0][3], $opCodes[0][4] - $context),
				$opCodes[0][4]
			);
		}

		$lastItem = count($opCodes) - 1;
		if ($opCodes[$lastItem][0] == 'equal') {
			list($tag, $i1, $i2, $j1, $j2) = $opCodes[$lastItem];
			$opCodes[$lastItem] = array(
				$tag,
				$i1,
				min($i2, $i1 + $context),
				$j1,
				min($j2, $j1 + $context)
			);
		}

		$maxRange = $context * 2;
		$groups = array();
		$group = array();
		foreach ($opCodes as $code) {
			list($tag, $i1, $i2, $j1, $j2) = $code;
			if ($tag == 'equal' && $i2 - $i1 > $maxRange) {
				$group[] = array(
					$tag,
					$i1,
					min($i2, $i1 + $context),
					$j1,
					min($j2, $j1 + $context)
				);
				$groups[] = $group;
				$group = array();
				$i1 = max($i1, $i2 - $context);
				$j1 = max($j1, $j2 - $context);
			}
			$group[] = array(
				$tag,
				$i1,
				$i2,
				$j1,
				$j2
			);
		}

		if (!empty($group) && !(count($group) == 1 && $group[0][0] == 'equal')) {
			$groups[] = $group;
		}

		return $groups;
	}
	
	private function getChangeExtent($fromLine,$toLine) {
		$start = 0;
		$limit = min(mb_strlen($fromLine),mb_strlen($toLine));
		while ($start < $limit && mb_substr($fromLine,$start,1) == mb_substr($toLine,$start,1)) {
			++$start;
		}
		
		$end = -1;
		$limit = $limit - $start;
		while (-$end <= $limit && mb_substr($fromLine,$end,1) == mb_substr($toLine,$end,1)) {
			--$end;
		}
		
		return array($start,$end + 1);
	}
	
	private function formatLines($lines) {
		$lines = array_map(array($this,'expandTabs'),$lines);
		$lines = array_map(array($this,'htmlSafe'),$lines);
		foreach ($lines as &$line) {
			$line = preg_replace_callback('# ( +)|^ #',array($this,'fixSpaces'),$line);
		}
		return $lines;
	}
	
	private function fixSpaces($matches) {
		if (count($matches) < 2) return '';
		
		$spaces = $matches[1];
		$count = strlen($spaces);
		if ($count == 0) return '';

		$div = floor($count / 2);
		$mod = $count % 2;
		return str_repeat('&nbsp; ', $div).str_repeat('&nbsp;', $mod);
	}
	
	private function expandTabs($line) {
		return str_replace("\t",str_repeat(' ', $this->options['tabSize']),$line);
	}

	private function htmlSafe($string) {
		return htmlspecialchars($string,ENT_NOQUOTES,'UTF-8');
	}
	
	private function tupleSort($leftSequence, $rightSequence) {
		$max = max(count($leftSequence), count($rightSequence));
		for ($i = 0; $i < $max; ++$i) {
			if ($leftSequence[$i] < $rightSequence[$i]) {
				return -1;
			} elseif ($leftSequence[$i] > $rightSequence[$i]) {
				return 1;
			}
		}

		if (count($leftSequence) == $count($rightSequence)) {
			return 0;
		} elseif (count($leftSequence) < count($rightSequence)) {
			return -1;
		} else {
			return 1;
		}
	}
}
?>