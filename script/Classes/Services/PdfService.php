<?php
namespace Drg\CloudApi\Services;

if (file_exists(SCR_DIR . 'Classes/Contributed/fpdf.php')){
		require_once( SCR_DIR . 'Classes/Contributed/fpdf.php');

		class PdfService extends \FPDF {

			/**
			* pageConf
			*
			* @var array
			*/
			Public $pageConf = array(
				'specchars' => array( 'laquo' => 171 , 'raquo' => 187 , 'copy' => 169 , 'C' => 169 , 'registered' => 174 , 'at' => 64 , 'et' => 38 ) , 
				'Title' => 'Title' , 
				'Keywords' => 'Keywords' , 
				'Subject' => 'Subject' , 
				'Footertext_left' => 'Footertext_left' , 
				'Footertext_right' => '__C__ __date_Y__ Footertext_right' , 
				'Fontfamily' => 'Helvetica' , 
				'ImagePath' => '' , 
				'ImageLeft'        => '10' , 
				'ImageTop'         => '9' , 
				'ImageWidth'       => '120' , 
				'TopMargin' => 30 , 
				'RightMargin' => 15 , 
				'BottomMargin' => 10 , 
				'LeftMargin' => 27.6 , 
				'Fontsize' => 10 , 
				'PdfCharset' => 'ISO-8859-15' , 
				'DataCharset' => 'UTF-8' , 
				'lfeed' => 5 , 
				'lineSmall' => 0.15 , 
				'lineBold' => 1.55 , 
				'lineWidth' => '0.2' ,
			);

			public function initializePdf( $pageConf = array() ) {
				
				if(count($pageConf)){
					foreach($pageConf as $key=>$value) {
							if(isset($this->pageConf[$key] )) $this->pageConf[$key] = $value;
					}
				}
				
				$this->SetTopMargin( $this->pageConf['TopMargin'] );
				$this->SetLeftMargin( $this->pageConf['LeftMargin'] );
				$this->SetRightMargin( $this->pageConf['RightMargin'] );
				$this->SetAutoPageBreak( TRUE , $this->pageConf['BottomMargin'] );
				$this->SetTextColor(0,0,0);
				$this->SetDrawColor(0,0,0);
				$this->SetFont( $this->pageConf['Fontfamily'] , '' , $this->pageConf['Fontsize'] );
				$this->SetLineWidth(  $this->pageConf['lineWidth'] );
				$this->SetKeywords(  $this->encode($this->pageConf['Keywords']) );
				$this->SetSubject(  $this->encode($this->pageConf['Subject']) );
				$this->SetTitle(  $this->encode($this->pageConf['Title']) );
				
			}
			
			function encode( $value ) {
				$value =  iconv( $this->pageConf['DataCharset'] , $this->pageConf['PdfCharset'] ,$value);
				$value = str_replace( '__date_long__' , date( 'd.m.Y' ) , $value);
				$value = str_replace( '__date_Y__' , date( 'Y' ) , $value);
				foreach( $this->pageConf['specchars'] as $charStr => $charNr ) {
					$value = str_replace( '__' . $charStr . '__' , chr($charNr) , $value);
				}
				return $value;
			}
			
			function Header() {
			if( file_exists( $this->pageConf['ImagePath'] ) && is_file($this->pageConf['ImagePath']) ){
					$this->Image( $this->pageConf['ImagePath'] , $this->pageConf['ImageLeft'] , $this->pageConf['ImageTop'] , $this->pageConf['ImageWidth'] );
			}
			
			}
			
			function Footer() {
				$this->SetFont( $this->pageConf['Fontfamily'] , '' , $this->pageConf['Fontsize']-3 );
				$this->SetXY($this->pageConf['LeftMargin'],-15);
				$textLeft = $this->encode($this->pageConf['Footertext_left']);
				$textRight = $this->encode($this->pageConf['Footertext_right']);
				
				$this->Cell( 0 , $this->pageConf['lfeed'] , $textLeft );
				$this->Cell( 0 , $this->pageConf['lfeed'] , $textRight , '' , 0 , 'R' );
				$this->SetFont( $this->pageConf['Fontfamily'] , '' , $this->pageConf['Fontsize'] );
			}
		}
		// Handle special IE contype request
		if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT']=='contype')
		{
			header('Content-Type: application/pdf');
			exit;
		}
}
?>
