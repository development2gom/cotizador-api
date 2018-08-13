<?php
namespace app\models;

use Yii;
class Email{
  
	public $emailHtml;
	public $emailText;
	public $from;
	public $to;
	public $subject;
	public $params;
	

	function __construct() {
		$this->from = Yii::$app->params ['modUsuarios'] ['email'] ['emailActivacion']; 
	}

	/**
	 * Envia mensaje de correo electronico
	 *   	
	 * @return boolean
	 */
	public function sendEmail() {
		return Yii::$app->mailer->compose ( [
				// 'html' => '@app/mail/layouts/example',
				// 'text' => '@app/mail/layouts/text'
				'html' => $this->emailHtml,
				//'text' => $templateText 
		], $this->params )->setFrom ( $this->from )->setTo ( $this->to )->setSubject ( $this->subject )->send ();
	}
}