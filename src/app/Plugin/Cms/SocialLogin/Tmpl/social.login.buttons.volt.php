<div class="uk-margin">
    <div class="uk-heading-line uk-text-center uk-margin">
        <span><?= MaiVu\Hummingbird\Lib\Helper\Text::_('sl-or-login-with') ?></span>
    </div>

    <?php if ($pluginConfig->get('params.facebookLogin') === 'Y') { ?>
        <div class="uk-margin-small">
            <a class="uk-button uk-width-1-1 SocialLogin-button-facebook" href="<?= $this->escaper->escapeHtmlAttr($fbLoginUrl) ?>">
                <span uk-icon="icon: facebook"></span>
                <?= MaiVu\Hummingbird\Lib\Helper\Text::_('sl-facebook') ?>
            </a>
        </div>
    <?php } ?>

    <?php if ($pluginConfig->get('params.googleLogin') === 'Y') { ?>
        <div class="uk-margin-small">
            <a class="uk-button uk-width-1-1 SocialLogin-button-google" href="<?= $this->escaper->escapeHtmlAttr($ggLoginUrl) ?>">
                <span uk-icon="icon: google"></span>
                <?= MaiVu\Hummingbird\Lib\Helper\Text::_('sl-google') ?>
            </a>
        </div>
    <?php } ?>
</div>