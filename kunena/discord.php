<?php
/**
 * @package    kunena-discord
 *
 * @author     michael <michael@mp-development.de>
 * @copyright  Michael Pfister
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://github.com/pfitzer/kunena-discord
 */

use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

/**
 * Kunena-discord plugin.
 *
 * @package   kunena-discord
 * @since     1.0.0
 */
class plgKunenaDiscord extends CMSPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	protected $webhooks = array();

    /**
     * plgSystemKunenaDiscord constructor.
     * @param $subject
     * @param array $config
     */
    public function __construct($subject, array $config = array())
    {
        parent::__construct($subject, $config);
        $this->webhooks["player"] = $this->params->get('webhook_p');
        $this->webhooks["creator"] = $this->params->get('webhook_c');
        $this->webhooks["mentor"] = $this->params->get("webhook_m");
        if (!sizeof($this->webhooks)) {
            throw new InvalidArgumentException("Webhook can`t be null. Please configure a webhook.");
        }
    }

    /**
     * Get Kunena activity stream integration object.
     *
     * @return \KunenaDiscord|null
     * @since Kunena
     */
    public function onKunenaGetActivity()
    {
        require_once __DIR__ . "/push.php";
        return new KunenaDiscord($this->webhooks);
    }

}
