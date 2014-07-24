<?php
/*
 * @copyright Copyright (c) 2014 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package message_badge
 * @author Mark Nielsen
 */

namespace theme_snap;

/**
 * Message Model
 *
 * @author Mark Nielsen
 * @package theme_snap
 */
class message implements \renderable {

    /**
     * @var int
     */
    public $useridfrom;

    /**
     * @var int
     */
    public $useridto;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $fullmessage;

    /**
     * @var int
     */
    public $fullmessageformat;

    /**
     * @var string
     */
    public $fullmessagehtml;

    /**
     * @var string
     */
    public $smallmessage;

    /**
     * @var int
     */
    public $notification;

    /**
     * @var string
     */
    public $contexturl;

    /**
     * @var string
     */
    public $contexturlname;

    /**
     * @var int
     */
    public $timecreated;

    /**
     * @var int
     */
    public $unread;

    /**
     * The user that the message is from (usually partial object)
     *
     * @var stdClass
     */
    protected $fromuser;

    public function __construct($options = array()) {
        $this->set_options($options);
    }

    /**
     * @param stdClass $user
     * @return message
     */
    public function set_fromuser(\stdClass $user) {
        if ($user->id != $this->useridfrom) {
            throw new coding_exception("The passed user->id ($user->id) != message->useridfrom ($this->useridfrom)");
        }
        $this->fromuser = $user;
        return $this;
    }

    /**
     * Will go to the DB and grab the user if not already set
     *
     * @throws coding_exception
     * @return stdClass
     */
    public function get_fromuser() {
        global $DB;

        if (is_null($this->fromuser)) {
            if (empty($this->useridfrom)) {
                throw new coding_exception('The message useridfrom is not set');
            }
            $this->set_fromuser(
                $DB->get_record('user', array('id' => $this->useridfrom), user_picture::fields(), MUST_EXIST)
            );
        }
        return $this->fromuser;
    }

    /**
     * A way to bulk set model properties
     *
     * @param array|object $options
     * @return message_output_badge_model_message
     */
    public function set_options($options) {
        foreach ($options as $name => $value) {
            // Ignore things that are not a property of this model
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
        return $this;
    }
}
