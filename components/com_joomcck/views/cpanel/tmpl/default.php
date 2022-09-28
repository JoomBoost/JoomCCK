<?php
/**
 * by JoomBoost
 * a component for Joomla! 3.x CMS (http://www.joomla.org)
 * Author Website: https://www.joomBoost.com/
 * @copyright Copyright (C) 2007-2014 JoomBoost (https://www.joomBoost.com). All rights reserved.
 * @license   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');
?>

<?php echo HTMLFormatHelper::layout('navbar'); ?>

<div class="page-header mb-3">
    <h1><?php echo JText::_('C_CPANEL') ?></h1>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">
				    <?php echo JText::_('CAPPBUILD'); ?>
                </h5>
            </div>
            <div class="card-body">

                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('section&layout=fast') ?>">
                    <i class="fas fa-shipping-fast fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_QUICKSTART') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('sections') ?>">
                    <i class="fas fa-th-list fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_SECTIONS') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('ctypes') ?>">
                    <i class="fas fa-puzzle-piece fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_TYPES') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('templates') ?>">
                    <i class="fas fa-palette fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_TEMPLATES') ?>
                    </div>
                </a>

            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">
				    <?php echo JText::_('CCONTENT'); ?>
                </h5>
            </div>
            <div class="card-body">


                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('items') ?>">
                    <i class="fas fa-file fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_RECORDS') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('votes') ?>">
                    <i class="fas fa-poll fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_VOTES') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('comms') ?>">
                    <i class="fas fa-comment fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_COMMENTS') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('tags') ?>">
                    <i class="fas fa-tag fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_TAGS') ?>
                    </div>
                </a>

            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card ">
            <div class="card-header">
                <h5 class="m-0">
				    <?php echo JText::_('CCONTENTTOOLS'); ?>
                </h5>
            </div>
            <div class="card-body">


                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('packs') ?>">
                    <i class="fas fa-cube fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_PACK') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('moderators') ?>">
                    <i class="fas fa-user-check fa-2x"></i>
                    <div>
					    <?php echo JText::_('CMODERATORS') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('tools') ?>">
                    <i class="fas fa-tools fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_TOOLS') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('auditlog') ?>">
                    <i class="fas fa-tasks fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_AUDIT') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('notifications') ?>">
                    <i class="fas fa-bell fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_NOTIFY') ?>
                    </div>
                </a>
                <a class="btn btn-light border shadow-sm me-2" href="<?php echo Url::view('import') ?>">
                    <i class="fas fa-upload fa-2x"></i>
                    <div>
					    <?php echo JText::_('XML_SUBMENU_IMPORT') ?>
                    </div>
                </a>

            </div>
        </div>
    </div>



</div>


