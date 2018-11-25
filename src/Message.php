<?php
/**
 * Created by PhpStorm.
 * User: macbook
 * Date: 2018/11/25
 * Time: 10:33 AM
 */
namespace  shane\mailerqueue;
use Yii;

class Message extends \yii\swiftmailer\Message
{
    public function queue()
    {
        $redis = Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfigException('redis not found in config.');
        }
        // 0 - 15  select 0 select 1
        // db => 1
        $mailer = Yii::$app->mailer;
        if (empty($mailer) || !$redis->select($mailer->db)) {
            throw new \yii\base\InvalidConfigException('db not defined.');
        }
        $message = [];

        $message['from'] = array_keys($this->from);
        $message['to'] = array_keys($this->getTo());
        if(!empty($this->getCc())){
            $message['cc'] = array_keys($this->getCc());
        }

        if(!empty($this->getBcc())){
            $message['bcc'] = array_keys($this->getBcc());
        }

        if(!empty($this->getReplyTo())){
            $message['reply_to'] = array_keys($this->getReplyTo());
        }

        if(!empty($this->getCharset())){
            $message['charset'] = array_keys($this->getCharset());
        }

//        if(!empty($this->getSubject())){
//            $message['subject'] = array_keys($this->getSubject());
//        }

        if(!empty($this->getSubject())){
            $message['subject'] = $this->getSubject();
        }

        $parts = $this->getSwiftMessage()->getChildren();
        if (!is_array($parts) || !sizeof($parts)) {
            $parts = [$this->getSwiftMessage()];
        }
        foreach ($parts as $part) {
            if (!$part instanceof \Swift_Mime_Attachment) {
                switch($part->getContentType()) {
                    case 'text/html':
                        $message['html_body'] = $part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body'] = $part->getBody();
                        break;
                }
//                if (!$message['charset']) {
//                    $message['charset'] = $part->getCharset();
//                }
            }
        }
        return $redis->rpush($mailer->key, json_encode($message));
    }
}
