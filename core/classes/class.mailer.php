<?php

/**
 * Todo: Return $this on functions so I can link methods together
 * =>   $mail = $objMailer->setHtml()
 *                        ->useSMTP()
 *                        ->addAddress('test@test.com')
 *                        ->setSubject('testing email')
 *                        ->setBody($bodyText)
 *                        ->send();
 */
class Mailer extends coreObj{

    /**
     * Protected class variables
     */
    protected   $contentType  = 'text/plain',
                $charSet      = 'iso-8859-1',
                $from         = 'noreply@cybershade.org',
                $fromName     = 'NoReply',
                $to           = array(),
                $cc           = array(),
                $bcc          = array(),
                $replyTo      = array(),
                $subject      = '',
                $body         = '',
                $wordWrap     = false,
                $mailType     = 'mail',
                $attachments  = array();


    public function __construct(){

    }

    /**
     * Sets the email to use the HTML encoding type
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @return  Object
     */
    public function setHtml(){
        $this->setVar('contentType', 'text/html');
        return $this;
    }

    /**
     * Sets the send method to be via SMTP
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @return  Object
     */
    public function useSMTP(){
        $this->setVar('mailType', 'smtp');
        return $this;
    }

    /**
     * Adds a new address to the mail
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @param   string  $address
     * @param   string  $name
     *
     * @return  Object
     */
    public function addAddress( $address, $name = '' ){
        $currentCount = count($this->to);

        // Stripslashes to prevent CRLF injection
        $this->to[$currentCount][0] = trim( stripslashes( $address ) );
        $this->to[$currentCount][1] = stripslashes($name);

        return $this;
    }

    /**
     * Adds a new CC address to the mail
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @param   string  $address
     * @param   string  $name
     *
     * @return  Object
     */
    public function addCC( $address, $name = '' ){
        $currentCount = count($this->cc);

        // Stripslashes to prevent CRLF injection
        $this->cc[$currentCount][0] = trim( stripslashes( $address ) );
        $this->cc[$currentCount][1] = stripslashes( $name );

        return $this;
    }

    /**
     * Sets the email subject
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @param   string  $subject
     *
     * @return  Object
     */
    public function setSubject( $subject = '' ){
        $this->setVar('subject', $subject);
        return $this;
    }

    /**
     * Adds the ReplyTo address and name to the email
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @param   string  $address
     * @param   string  $name
     *
     * @return  Object
     */
    public function addReplyTo( $address, $name = '' ){
        $currentCount = count($this->replyTo);

        // Stripslashes to prevent CRLF injection
        $this->replyTo[$currentCount][0] = trim( stripslashes( $address ) );
        $this->replyTo[$currentCount][1] = stripslashes( $name );

        return $this;
    }

    public function setBody( $body ){
        $this->setVar( 'body', $body );
        return $this;
    }

    /**
     * Sends the email
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @return  bool
     */
    public function send(){
        if( count( $this->to ) < 1 ){
            trigger_error('You must provide at least one recipient email address');
            return false;
        }

        $header   = $this->createHeader();
        $body     = $this->createBody();
        $mailType = $this->getVar('mailType');
        $mail     = $this->sendMail( $header, $body, ( !$mailType ? 'mail' : $mailType ) );

        return $mail;
    }

    /**
     * Adds a new address to the mail
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Richard Clifford
     *
     * @access protected
     *
     * @param   string  $headers
     * @param   string  $body
     * @param   string  $type   The mail type (SMTP, mail) Defaults to 'mail'
     *
     * @return  Object
     */
    protected function sendMail( $headers, $body, $type = 'mail' ){
        if( is_empty( $mail ) ){
            trigger_error('You must specify a valid send mode');
            return false;
        }


        $server = $_SERVER['HTTP_HOST'];

        // Set up the headers
        $headers[] = sprintf('From: %s <%s>', $fromName, $from);
        $headers[] = sprintf('Reply-To: %s <%s>', $fromName, $from);
        $headers[] = sprintf('Return-Path: %s <%s>', $fromName, $from);
        $headers[] = 'Date: '.date('r', time());
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Message-ID: <'.md5(uniqid(time())).'@'.$server.'>';
        $headers[] = sprintf('Content-Type: %s; charset="iso-8859-1"', $contentType);
        $headers[] = 'X-Mailer: PHP v'.phpversion();
        $headers[] = 'X-Priority: 3';
        $headers[] = 'X-MSMail-Priority: Normal';
        $headers[] = 'X-MimeOLE: Produced By CybershadeCMS '.cmsVERSION;

        $objPlugins = coreObj::getPlugins();
        $objPlugin->hook('MAILER_HEADERS', $headers);

        // Set up the rest of the email details
        $to          = secureMe($this->getVar('to'));
        $subject     = secureMe($this->getVar('subject'));
        $from        = secureMe($this->getVar('from'));
        $fromName    = secureMe($this->getVar('fromName'));
        $body        = secureMe($this->getVar('body'));
        $contentType = secureMe($this->getVar('contentType'));

        if( !$to || count( $to ) < 1 ){
            trigger_error('You must specify valid addresses to send the mail to');
            return false;
        }

        // Switch on the mail type
        switch( strtolower( $mail ) ){
            case 'smtp':
                // Do later
                break;

            // Default mail type
            case 'mail':
            default:
                $sendTo = $to[0][0];

                if( count( $to ) > 1 ){
                    foreach($to as $name => $addrTo){
                        $sendTo .= sprintf(',%s', $addrTo);
                    }
                }

                $sendMail = mail( $to, $subject, $body, implode(" \n", $headers) );

                if( $sendMail || $sendMail == '' ){
                    return true;
                }
                return false;
                break;
        }
    }
}

?>