<?php
namespace Drg\CloudApi\Services;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Daniel Rueegg <colormixture@verarbeitung.ch>
 *
 *  All rights reserved
 *
 *  This script is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class SyntaxService {

	/**
	 * Property lines
	 *
	 * @var array
	 */
	Public $lines = array( 
			0 => array(
					'info' => array( 'section'=>'' ,  'listtags'=>'' ,  'tagposition'=>'' , 'empty'=>FALSE , 'islast'=>FALSE ) , 
					'content' => '' 
			)
	);

	/**
	 * Property styles
	 *
	 * @var array
	 */
	Public $styles = array(
			'P' => 'text-indent:-1em;padding:0;margin:0 1em;',
			'DIV' => 'padding:0;margin:0 0 0.5em 0;',
			'list-types' => array( 
				'*'=>array('outer'=>'ul','inner'=>'li') , 
				'-'=>array('outer'=>'ol','inner'=>'li') , 
				'+'=>array('outer'=>'pre','inner'=>'') 
			),
			// elements with divergent open- and close tags. Content may be rendered by method w2h_wrap()
			'elements' => array(
				'link' => array( '[[' , ']]' ),
				'aquo' => array( '^^' , '^^' ),
			),
			// tags must be repeatingly single char (not mixed)
			'tags' => array(
					'b' => '**',
					'i' => '//',
					'u' => '__',
					'del' => '--',
					'H1' => '======',
					'H2' => '=====',
					'H3' => '====',
					'H4' => '===',
					'H5' => '==',
			),
			// block describes specific tags, therefore values in 'block' must appear in 'tags'.
			'block' => array( 'H2' , 'H3' , 'H4' , 'H5'),
			'entities' => array( '->' => '&rarr;' , '<-' => '&larr;' , '=>' => '&Rarr;' , '<=' => '&Larr;' , '_c_'=>'&copy;'  ),
	);



    /**
    * wikiToHtml
    * Convert content with wiki syntax to html tagged content
    *
    * @param string  $wikiContent
    * @return string
    */
    function wikiToHtml($wikiContent) {
			$htmlContent = '';
		
			$aOutCont = array();
			$aCnt = explode(  "\n" , html_entity_decode($wikiContent) );
			
			// detect list-sections
			$aLiCount = $this->w2h_detectListSections($aCnt);
			
			// append styled lines to output
			$absNr = 0;
			foreach( $aCnt as $ix => $untrimmedLine){
			
					$line = $this->lines[$ix]['content'];
					// increase sectionNr if empty line
					$absNr = $this->lines[$ix]['info']['section'];
					
					// detect type: list or usual div?
					// wrap as list or usual p-block and append to array
					if( isset($this->lines[$ix]['info']['listtags']) ){
					// append to list if this is a list-element AND there are more than 1 Liste elements
							// detect list-type depending on char - or *
							$listChar = $this->lines[$ix]['info']['listtags'];
							$elementContent = $this->w2h_lineStyleTags( ltrim($line,$listChar) );
							
							// define an empty array or insert the list-start tag, if this list-entry is the first one
							$aOutCont[$absNr][$ix] = $this->lines[$ix]['info']['tagposition'] == 1 ? '<'.$this->styles['list-types'][$listChar]['outer'].'>' : '';
							
							// append the list element
							if( !empty($this->styles['list-types'][$listChar]['inner']) ){
									$aOutCont[$absNr][$ix].= '<'.$this->styles['list-types'][$listChar]['inner'].'>' . $elementContent . '</'.$this->styles['list-types'][$listChar]['inner'].'>';
							}else{
									$aOutCont[$absNr][$ix].=  $elementContent ;
							}
							// append the list-end tag, if this list-entry is the last one
							if( $this->lines[$ix]['info']['islast'] ){ 
									$fulltag = isset($this->styles['list-types'][$listChar]) ? '</'.$this->styles['list-types'][$listChar]['outer'].'>' : '';
									if( !isset($aOutCont[$absNr][$ix]) ) $aOutCont[$absNr][$ix] = '';
									$aOutCont[$absNr][$ix].= $fulltag; 
							}
					}else{
					// wrap line as p 
							// if empty line follow or is last line and empty line trails, then render as p else as br
							if( trim($line) == '' ){
								$aOutCont[$absNr][$ix] = $line;
							}elseif( !isset($aOutCont[$absNr][$ix+1]) && !isset($aOutCont[$absNr][$ix-1]) ){
								// single line 
								$aOutCont[$absNr][$ix] = $this->w2h_lineStyleTags($line , 'P');
							}elseif( !isset($aOutCont[$absNr][$ix+1]) && '' == trim($aOutCont[$absNr][$ix-1]) ){
								//  last line in section and empty leading line
								$aOutCont[$absNr][$ix] = $this->w2h_lineStyleTags($line , 'P');
							}elseif( !isset($aOutCont[$absNr][$ix-1]) && '' == trim($aOutCont[$absNr][$ix-1]) ){
								// first line in section and empty following line
								$aOutCont[$absNr][$ix] = $this->w2h_lineStyleTags($line , 'P');
							}else{
								$aOutCont[$absNr][$ix] = $this->w2h_lineStyleTags($line ) . '<br />';
							}
					}
			}
			
			// wrap every section as div
			foreach($aOutCont as $absatzNr => $aAbsatz ) {
					$htmlContent .= "\n<div style=\"".$this->styles['DIV']."\">". implode( "\n" , $aAbsatz ). "\n</div>";
			}
			// replace entities like arrows -> or copyright-sign _c_ 
			$htmlContent = $this->w2h_replaceEntities($htmlContent);
			return $htmlContent;
    }

    /**
    * isInString 
    * searches for occurence of one or more needles
    * in a string $haystack
    * Needles are values of the array $aNeedles
    * returns a percent value about how exactly it matches
    *
    * @param string  $haystack
    * @param array  $aNeedles
    * @param string  $fullmatch [ 0 = strpos | 1 = strpos sensitive | 2 = match | 3 = match sensitive ]
    * @return integer
    */
    function isInString( $haystack , $aNeedles , $fullmatch = 0 ) {
		$match = 0;
		if( $fullmatch == 0 ){
			foreach( $aNeedles as $needle ) if( stripos( ' ' . $haystack , $needle ) ) ++$match;
		}elseif( $fullmatch == 1 ){
			foreach( $aNeedles as $needle ) if( strpos( ' ' . $haystack , $needle ) ) ++$match;
		}elseif( $fullmatch == 2 ){
			foreach( $aNeedles as $needle ) if( strtolower($haystack) == strtolower($needle) ) ++$match;
		}else{
			foreach( $aNeedles as $needle ) if( $haystack == $needle ) ++$match;
		}
		return $match / count($aNeedles);
    }

    /**
    * w2h_detectListSections 
    * helper for wikiToHtml
    *
    * @param array  $aContent
    * @return array
    */
    function w2h_detectListSections($aContent) {
			$absNr = 0;
			// detect list-sections
			$aListtags = array();
			foreach( $this->styles['block'] as $el ) $aNeedles[$el] = $this->styles['tags'][$el];
			foreach( $aContent as $ix => $untrimmedLine){
					$line = trim($untrimmedLine);
					// increase sectionNr if empty line
					$fits = $this->isInString( $line , $aNeedles );
					if( $line == '' || $fits > 0 ) ++$absNr ; 
					// count list-elements per section
					foreach(  $this->styles['list-types']  as $listChar => $tagConf ){
							$lineStarter = substr( $line , 0 , strlen($listChar)+1 );
							if( $lineStarter == $listChar . ' ' ) { // char has to have trailing space like '- ' or '* '
									$sections[$absNr][$listChar][] = $ix;
									$this->lines[$ix]['info']['listtags'] = $listChar;
									$this->lines[$ix]['info']['tagposition'] = count( $sections[$absNr][$listChar] ) ;
									break; /// only one list-type per line possible
							}
					}
					$this->lines[$ix]['content'] = $line;
					$this->lines[$ix]['info']['empty'] = $line == '' ? TRUE : FALSE;
					$this->lines[$ix]['info']['section'] = $absNr;
			}
			foreach( $this->lines as $ix => $lineConf){
				if( isset($lineConf['info']['listtags']) ) {
						$sectionNr = $this->lines[$ix]['info']['section'];
						$listTags = $lineConf['info']['listtags'];
						$countSections = isset($sections[ $sectionNr ][ $listTags ]) ? count( $sections[ $sectionNr ][ $listTags ] ) : 0;
						$tagPosition = $this->lines[$ix]['info']['tagposition'];
						$this->lines[$ix]['info']['islast'] = $countSections == $tagPosition ;
				}
			}
			return ;
	}


    /**
    * w2h_lineStyleTags
    * helper for wikiToHtml
    * translates wiki style-tags to html tags
    *
    * @param string  $strLine
    * @return string
    */
    function w2h_lineStyleTags( $strLine , $wrapType = '' ) {
		$renderedText = $strLine;
		$wrappedAsBlock = 0;
		$matchingTags = array();
		
		foreach( $this->styles['tags'] as $htmlTag => $wikiTag ){
				// detect first pair of tags. 
				$matchingTags[$htmlTag] = $this->w2h_findAllPairs( $renderedText , $wikiTag , $wikiTag );
				if( !is_array($matchingTags[$htmlTag][0]) ) continue; // renderedText stays at it is
				
				if( in_array($htmlTag,$this->styles['block']) ) $wrappedAsBlock = 1;
				
				// build rendered text
				$newRenderedLine = '';
				foreach( $matchingTags[$htmlTag] as $blockNr => $tagBlock ){
					// glue the parts until ending tag together, without trailing text
					$newRenderedLine .= $tagBlock['prependText'] . $this->w2h_wrap( $htmlTag , $tagBlock['bodyText']  );
					// handle the trailing text: if no further tags of this sort are in line then append raw text
					if( $tagBlock['islast'] ) $newRenderedLine .= $tagBlock['appendText'] ;
				}
				$renderedText = $newRenderedLine;
				
		}
		$z=0;
		foreach( $this->styles['elements'] as $elementName => $aWikiTags ){
				++$z;
				// detect first pair of tags. 
				$matchingTags[$elementName] = $this->w2h_findAllPairs( $renderedText , $aWikiTags[0] , $aWikiTags[1] );
				if( !is_array($matchingTags[$elementName][0]) ) continue; // renderedText stays at it is

				if( in_array($elementName,$this->styles['block']) ) $wrappedAsBlock = 1;
				
				// build rendered text
				$newRenderedLine = '';
				foreach( $matchingTags[$elementName] as $blockNr => $tagBlock ){
					// glue the parts until ending tag together, without trailing text
					$newRenderedLine .= $tagBlock['prependText'] . $this->w2h_wrap( $elementName , $tagBlock['bodyText']  );
					// handle the trailing text: if no further tags of this sort are in line then append raw text
					if( $tagBlock['islast'] ) $newRenderedLine .= $tagBlock['appendText'] ;
				}
				$renderedText = $newRenderedLine;
				
		}
		if( $wrappedAsBlock || empty($wrapType) || !isset( $this->styles[strtoupper($wrapType)] ) ) return $renderedText;
		
		if( empty( $renderedText ) ) return $renderedText ;
		
		return '<'.strtoupper($wrapType).' style="'.$this->styles[strtoupper($wrapType)].'">' . $renderedText . '</'.strtoupper($wrapType).'>';
	}
	
    function w2h_replaceEntities( $text ) {
			$search = array_keys( $this->styles['entities'] );
			$replace = $this->styles['entities'];
			return str_replace( $search , $replace , $text );
	}
	
    function w2h_wrap( $element , $blocktext ) {
		$strOptions = '';
		switch( $element ){
			case 'link' : 
				$htmlTag = 'A';
				$tr = array( 'intern'=>'_self' , 'extern'=>'_blank'  );
				$atxt = explode( '|' ,$blocktext );
				
				if( count($atxt) == 1 ){ // only url
					$tagOptions = array('href'=>trim($atxt[0]),'target'=>'_blank');
				}elseif( count($atxt) == 2 ){ // url + label
					$tagOptions = array('href'=>trim($atxt[0]),'target'=>'_blank');
					$blocktext = $atxt[1];
				}elseif( count($atxt) >= 3 ){ // url, label + target=default +title=default
					$tagOptions = array('href'=>trim($atxt[0]),'target'=> trim($atxt[2]) );
					if( isset($atxt[3]) ) $tagOptions['title'] =  $atxt[3] ;
					$blocktext = $atxt[1];
					$aAddPart = array_slice( $atxt , 2 );
					foreach( $aAddPart as $optBlock ){
						$aOpt = explode( ':' , trim($optBlock));
						if( count($aOpt) <2 ) continue;
						$tagOptions[trim($aOpt[0])] = trim( implode( ':' , array_slice( $aOpt , 1 )) );
					}
				}
				if( !isset( $tagOptions['title'] ) ) $tagOptions['title'] = '##LL:link_' . str_replace( $tr , array_keys($tr) , $tagOptions['target']) . '## ' . $tagOptions['href'] ;
				if( isset( $tagOptions['target'] ) ) $tagOptions['target'] = str_replace( array_keys($tr) , $tr , $tagOptions['target'] );
				foreach( $tagOptions as $opt => $cont ){
					$strOptions .= ' ' . trim($opt) . '="' .trim($cont. '"') ;
				}
			break;
			case 'aquo':
				return '&laquo;' . $blocktext . '&raquo;';
			break;
			default:
				$htmlTag = $element;
			break;
		}
		return '<' . $htmlTag . $strOptions . '>' . $blocktext . '</' . $htmlTag . '>';
		
	}
	
    function w2h_findAllPairs( $renderedText , $wikiTagOpen , $wikiTagClose  ) {
				// detect first pair of tags. 
				$matchingTags = array( 0 => $this->w2h_findPair( $renderedText , $wikiTagOpen , $wikiTagClose ) );
				if( !is_array($matchingTags[0]) ) return FALSE; // renderedText stays at it is
				// loop to detect more tags
				for( $z = 1 ; $z <= floor( strlen($renderedText) / ( strlen($wikiTagOpen)+strlen($wikiTagClose)+1 ) ) ; ++$z ){ // $z <= maximalTags
					if( $matchingTags[ $z-1 ]['islast'] ) break; // only 1 pair
					$matchingTags[ $z ] = $this->w2h_findPair( $matchingTags[ $z-1 ]['appendText'] , $wikiTagOpen , $wikiTagClose );
					// if only found once, matchingTags is empty!
					if( !is_array($matchingTags[ $z ]) || $matchingTags[ $z ]['islast'] ) break; // stop if is last or empty
				}
				return $matchingTags;
	}
	
    function w2h_findPair( $strLine , $wikiTagOpen , $wikiTagClose  ) {
		$clnLine = strtolower($strLine);
		
		$firstFoundPos = stripos( $clnLine , $wikiTagOpen );
		if( FALSE === $firstFoundPos ) return FALSE; // not found at all
		
		$lastPos = strripos( $clnLine , $wikiTagClose );
		if( empty($lastPos) || $lastPos === $firstFoundPos ) return FALSE; // only found once
		
		// firstPos: if same char follows as next then jump over
		$firstPos = $this->w2h_checkForMultipleTagchars( $clnLine , $wikiTagOpen , $firstFoundPos , $lastPos );
		if( $lastPos <= $firstPos ) return FALSE; // only found repeatingly chars like *** insteaf of **
		
		// nextPos: if same char follows as next then jump over
		$nextFoundPos = stripos( $clnLine , $wikiTagClose , $firstPos+1 );
		$nextPos = $this->w2h_checkForMultipleTagchars( $clnLine , $wikiTagClose , $nextFoundPos  , $lastPos );
		// hack for case of the char was found only once and not as pair ( eg. chars // where found in URLs and in italic-wiki-tags )
		
		if( empty($nextPos) )return FALSE; // only found once
		$charLen = strlen($wikiTagOpen);
		$startBodyText = $firstPos+$charLen;
		return array(
				 'openPos' => $firstPos ,
				 'closePos' => $nextPos ,
				 'tagLength' => $charLen ,
				 'prependText' => substr( $strLine , 0 , $firstPos ) ,
				 'bodyText' => substr( $strLine , $startBodyText , $nextPos - $startBodyText ) ,
				 'appendText' => substr( $strLine , $nextPos+$charLen ) ,
				 'islast' => $nextPos>=$lastPos ,
		);
		
	}
		
    function w2h_checkForMultipleTagchars( $clnLine , $chars , $firstPos , $nextPos ) {
		$singleSearchedChar = strtolower( substr($chars,0,1) );
		$len = strlen($chars);
		if( $singleSearchedChar == substr($clnLine,$firstPos+$len,1) ){
				$p = $this->w2h_findNextTextcharPosition( $clnLine , $singleSearchedChar , $firstPos , $nextPos);
				if( empty($p) ) return FALSE; // only found multiple in block like *** instead of **
				$firstPos = stripos( $clnLine , $chars , $p ); // 
				if( empty($firstPos) ) return FALSE; // only found multiple in block
		}
		return $firstPos;
	}
		
    function w2h_findNextTextcharPosition( $clnLine , $singleSearchedChar , $firstPos , $nextPos) {
			for( $p = $firstPos ; $p <= $nextPos ; ++$p ) { if( $singleSearchedChar != substr($clnLine,$p,1) ) break; }
			if( $p == $nextPos || empty($p) ) return FALSE; // only needle-chars until last found position
			return $p;
	}

	
    /**
    * Convert content with wiki syntax to txt
    *
    * @param string  $wikiContent
    * @return string
    */
    function wikiToTxt($wikiContent) {
		return $wikiContent;
    }

}

?>
