<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

namespace tsn\tsn\controller;

use phpbb\config\config;
use phpbb\language\language;
use phpbb\log\log;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;

/**
 * the-spot.net ACP controller.
 */
class acp_controller
{
    /** @var \phpbb\config\config */
    protected $config;
    /** @var \phpbb\language\language */
    protected $language;
    /** @var \phpbb\log\log */
    protected $log;
    /** @var \phpbb\request\request */
    protected $request;
    /** @var \phpbb\template\template */
    protected $template;
    /** @var \phpbb\user */
    protected $user;
    /** @var string Custom form action */
    protected $u_action;

    /**
     * Constructor.
     *
     * @param \phpbb\config\config     $config   Config object
     * @param \phpbb\language\language $language Language object
     * @param \phpbb\log\log           $log      Log object
     * @param \phpbb\request\request   $request  Request object
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\user              $user     User object
     */
    public function __construct(config $config, language $language, log $log, request $request, template $template, user $user)
    {
        $this->config = $config;
        $this->language = $language;
        $this->log = $log;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
    }

    /**
     * Display the options a user can configure for this extension.
     * @return void
     */
    public function display_options()
    {
        // Add our common language file
        $this->language->add_lang('common', 'tsn/tsn');

        // Create a form key for preventing CSRF attacks
        add_form_key('tsn_tsn_acp');

        // Create an array to collect errors that will be output to the user
        $errors = [];

        // Is the form being submitted to us?
        if ($this->request->is_set_post('submit')) {
            // Test if the submitted form is valid
            if (!check_form_key('tsn_tsn_acp')) {
                $errors[] = $this->language->lang('FORM_INVALID');
            }

            // If no errors, process the form data
            if (empty($errors)) {
                // Set the options the user configured
                $this->config->set('tsn_enable_extension', $this->request->variable('tsn_enable_extension', 1));
                $this->config->set('tsn_enable_myspot', $this->request->variable('tsn_enable_myspot', 1));
                $this->config->set('tsn_enable_miniprofile', $this->request->variable('tsn_enable_miniprofile', 1));
                $this->config->set('tsn_enable_miniforums', $this->request->variable('tsn_enable_miniforums', 1));
                $this->config->set('tsn_enable_newposts', $this->request->variable('tsn_enable_newposts', 1));
                $this->config->set('tsn_enable_specialreport', $this->request->variable('tsn_enable_specialreport', 1));
                $this->config->set('tsn_specialreport_forumid', $this->request->variable('tsn_specialreport_forumid', 1));
                $this->config->set('tsn_specialreport_excerpt_words', $this->request->variable('tsn_specialreport_excerpt_words', 140));

                // Add option settings change action to the admin log
                $this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_TSN_SETTINGS');

                // Option settings have been updated and logged
                // Confirm this to the user and provide link back to previous page
                trigger_error($this->language->lang('ACP_TSN_SETTING_SAVED') . adm_back_link($this->u_action));
            }
        }

        $s_errors = !empty($errors);

        // Set output variables for display in the template
        $this->template->assign_vars([
            'S_ERROR'   => $s_errors,
            'ERROR_MSG' => $s_errors ? implode('<br />', $errors) : '',

            'U_ACTION' => $this->u_action,

            'ACP_TSN_ENABLE_EXTENSION'          => $this->config['tsn_enable_extension'],
            'ACP_TSN_ENABLE_MYSPOT'             => $this->config['tsn_enable_myspot'],
            'ACP_TSN_ENABLE_MINIPROFILE'        => $this->config['tsn_enable_miniprofile'],
            'ACP_TSN_ENABLE_MINIFORUMS'         => $this->config['tsn_enable_miniforums'],
            'ACP_TSN_ENABLE_NEWPOSTS'           => $this->config['tsn_enable_newposts'],
            'ACP_TSN_ENABLE_SPECIALREPORT'      => $this->config['tsn_enable_specialreport'],
            'V_ACP_TSN_SPECIALREPORT_FORUMID'   => $this->config['tsn_specialreport_forumid'],
            'V_ACP_TSN_SPECIALREPORT_WORDCOUNT' => $this->config['tsn_specialreport_excerpt_words'],
        ]);
    }

    /**
     * Set custom form action.
     *
     * @param string $u_action Custom form action
     *
     * @return void
     */
    public function set_page_url($u_action)
    {
        $this->u_action = $u_action;
    }
}
