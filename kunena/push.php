<?php
/**
 * @package     kunena-discord
 * @subpackage
 *
 * @author     michael <michael@mp-development.de>
 * @copyright   Michael Pfister
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Http\Http;

/**
 * @package     ${NAMESPACE}
 *
 * @since version
 */
class KunenaDiscord extends KunenaActivity
{


    /**
     * @var null
     */
    private $webhooks = array();

    /**
     * @var \Joomla\CMS\Language\Language
     */
    private $lang;

    /**
     * @var \Joomla\CMS\Application\CMSApplication
     */
    private $app;

    /**
     * KunenaDiscord constructor.
     * @param $webhooks
     * @throws Exception
     */
    public function __construct($webhooks)
    {
        $this->webhooks = $webhooks;
        $this->lang = JFactory::getLanguage();
        $this->lang->load('plg_kunena_discord', JPATH_ADMINISTRATOR);
        $this->app = JFactory::getApplication();
    }

    /**
     * @param KunenaForumMessage $message
     */
    public function onAfterReply($message)
    {
        $this->_prepareAndSend(
            $message,
            JText::_("PLG_KUNENA_DISCORD_MESSAGE_NEW")
        );
    }

    /**
     * @param KunenaForumMessage $message
     */
    public function onAfterPost($message)
    {
        $this->_prepareAndSend(
            $message,
            JText::_("PLG_KUNENA_DISCORD_MESSAGE_NEW")
        );
    }

    /**
     * @param KunenaForumMessage $message
     * @return bool
     */
    private function _checkPermissions($message)
    {
        $category = $message->getCategory();
        $accesstype = $category->accesstype;

        if ($accesstype != 'joomla.group' && $accesstype != 'joomla.level') {
            return false;
        }

        if ($accesstype == "joomla.level") {
            switch($category->access) {
                case 5 : return "player";
                case 7 : return "creator";
                case 8 : return "mentor";
            }
        }

        return false;
    }

    /**
     * @param $pushMessage
     * @param $url
     * @param KunenaForumMessage $message
     */
    private function _send_message($pushMessage, $url, $message, $allowed)
    {
        $content = '**' . $pushMessage . '** *' . $message->subject . '* [Link](' . $url . ')';
        $hookObject = json_encode([
            /*
             * The general "message" shown above your embeds
             */
            "content" => $content,
            /*
             * The username shown in the message
             */
            "username" => $this->app->get('sitename'),
            /*
             * Whether or not to read the message in Text-to-speech
             */
            "tts" => false
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $request = new Http();
        $response = $request->post($this->webhooks[$allowed], utf8_encode($hookObject), ['Content-Type' => 'application/json']);
        if ($response->code != 204) {
            $body = json_decode($response->body);
            $this->app->enqueueMessage(JText::_('PLG_KUNENA_DISCORD_ERROR') . ' ' . $body->message, 'Warning');
        }
    }

    /**
     * @param KunenaForumMessage $message
     * @param $translatedMsg
     */
    private function _prepareAndSend($message, $translatedMsg)
    {
        $allowed = $this->_checkPermissions($message);
        if ($allowed) {
            $pushMessage = sprintf($translatedMsg, $message->subject);
            try {
                $url = htmlspecialchars_decode(JUri::base()
                    . mb_substr($message->getPermaUrl(), 1)
                    . '#' . $message->id);
                $this->_send_message($pushMessage, $url, $message, $allowed);
            } catch (Exception $e) {
                $this->app->enqueueMessage(JText::_('PLG_KUNENA_DISCORD_ERROR'), 'error');
            }
        }
    }
}
