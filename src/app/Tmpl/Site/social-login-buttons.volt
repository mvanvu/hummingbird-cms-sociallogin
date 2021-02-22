<div class="uk-margin">
    <div class="uk-heading-line uk-text-center uk-margin">
        <span>{{ _('sl-or-login-with') }}</span>
    </div>

    {% if pluginConfig.get('params.facebookLogin') === 'Y' %}
        <div class="uk-margin-small">
            <a class="uk-button uk-width-1-1 SocialLogin-button-facebook" href="{{ fbLoginUrl | escape_attr }}">
                <span uk-icon="icon: facebook"></span>
                {{ _('sl-facebook') }}
            </a>
        </div>
    {% endif %}

    {% if pluginConfig.get('params.googleLogin') === 'Y' %}
        <div class="uk-margin-small">
            <a class="uk-button uk-width-1-1 SocialLogin-button-google" href="{{ ggLoginUrl | escape_attr }}">
                <span uk-icon="icon: google"></span>
                {{ _('sl-google') }}
            </a>
        </div>
    {% endif %}
</div>