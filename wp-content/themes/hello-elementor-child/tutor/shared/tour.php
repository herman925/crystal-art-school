<?php
/**
 * Tutor's first-visit onboarding tour, switched off.
 *
 * Tutor loads this template into its dashboard and learning area for every student who has
 * not yet finished the tour: a blue modal with slides of a stock dashboard and a "Hi, Sophia!"
 * sample student, with its pictures fetched from Tutor's own storage. It is not the site's
 * design and shows the student a dashboard that is not theirs. This file is deliberately empty
 * of output. New students are also marked as having finished it, so if this override is ever
 * removed they still do not meet it: see cas_account_skip_tutor_tour() in inc/cas-account.php.
 */

defined( 'ABSPATH' ) || exit;
