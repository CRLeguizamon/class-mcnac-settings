jQuery(document).ready(function ($) {
    'use strict';

    const MCNAC_Chat = {
        init: function () {
            this.cacheDOM();
            this.renderHeaderLogo();
            this.bindEvents();
            this.sessionId = this.getSessionId();

            // Set initial state from settings
            this.$title.text(mcnacSettings.title);
            this.$subtitle.text(mcnacSettings.subtitle);
            this.$input.attr('placeholder', mcnacSettings.placeholder);

            // Render initial message
            if (mcnacSettings.initialMessage && !this.hasMessages()) {
                this.addMessage(mcnacSettings.initialMessage, 'bot');
            }
        },

        cacheDOM: function () {
            this.$widget = $('#mcnac-chat-widget');
            this.$toggle = $('#mcnac-chat-toggle');
            this.$window = $('#mcnac-chat-window');
            this.$close = $('#mcnac-chat-close');
            this.$messages = $('#mcnac-chat-messages');
            this.$input = $('#mcnac-chat-input');
            this.$send = $('#mcnac-chat-send');
            this.$title = this.$widget.find('.mcnac-title');
            this.$subtitle = this.$widget.find('.mcnac-subtitle');
            this.$logoContainer = this.$widget.find('.mcnac-header-logo-container');
        },

        renderHeaderLogo: function () {
            // Priority: Custom Logo > Default Logo > Fallback Icon (rare case)
            const logoUrl = mcnacSettings.logo || mcnacSettings.defaultLogo;

            if (logoUrl) {
                const $img = $('<img>').attr('src', logoUrl).attr('alt', 'Chat Logo');
                this.$logoContainer.html($img);
            } else {
                // Default Icon (User) - Fallback if image fails to load or isn't set
                const $icon = $(`
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                `);
                this.$logoContainer.html($icon);
            }
        },

        bindEvents: function () {
            this.$toggle.on('click', this.toggleChat.bind(this));
            this.$close.on('click', this.closeChat.bind(this));
            this.$send.on('click', this.sendMessage.bind(this));
            this.$input.on('keypress', (e) => {
                if (e.which === 13) {
                    this.sendMessage();
                }
            });
        },

        toggleChat: function () {
            this.$window.toggleClass('active');
            if (this.$window.hasClass('active')) {
                this.$input.focus();
                this.scrollToBottom();
            }
        },

        closeChat: function () {
            this.$window.removeClass('active');
        },

        getSessionId: function () {
            let sessionId = localStorage.getItem('mcnac_session_id');
            if (!sessionId) {
                sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('mcnac_session_id', sessionId);
            }
            return sessionId;
        },

        hasMessages: function () {
            return this.$messages.children('.mcnac-message').length > 0;
        },

        addMessage: function (text, type) {
            const $msg = $('<div>').addClass('mcnac-message ' + type).html(this.formatText(text));
            this.$messages.append($msg);
            this.scrollToBottom();
        },

        addTypingIndicator: function () {
            const $indicator = $('<div>').addClass('mcnac-message bot typing').attr('id', 'mcnac-typing');
            $indicator.append('<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>');
            this.$messages.append($indicator);
            this.scrollToBottom();
        },

        removeTypingIndicator: function () {
            $('#mcnac-typing').remove();
        },

        scrollToBottom: function () {
            this.$messages.scrollTop(this.$messages[0].scrollHeight);
        },

        formatText: function (text) {
            // Basic formatting (newlines to <br>)
            if (!text) return '';
            return text.replace(/\n/g, '<br>');
        },

        sendMessage: function () {
            const message = this.$input.val().trim();
            if (!message) return;

            this.addMessage(message, 'user');
            this.$input.val('');
            this.addTypingIndicator();

            // Prepare payload for n8n standard chat trigger
            // Usually expects: action=sendMessage, sessionId, chatInput
            const payload = {
                action: 'sendMessage',
                sessionId: this.sessionId,
                chatInput: message,
                metadata: {
                    pageTitle: document.title,
                    pageUrl: window.location.href
                }
            };

            // Log for debugging (user request)
            console.log('MCNAC Chat Sending:', payload);

            $.ajax({
                url: mcnacSettings.webhookUrl,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: (response) => {
                    this.removeTypingIndicator();
                    console.log('MCNAC Chat Response:', response);

                    if (Array.isArray(response)) {
                        response.forEach(item => {
                            if (item.output) this.addMessage(item.output, 'bot');
                            else if (item.text) this.addMessage(item.text, 'bot');
                            else if (typeof item === 'string') this.addMessage(item, 'bot');
                        });
                    } else if (typeof response === 'object') {
                        if (response.output) this.addMessage(response.output, 'bot');
                        else if (response.text) this.addMessage(response.text, 'bot');
                        else if (response.message) this.addMessage(response.message, 'bot');
                        else this.addMessage(JSON.stringify(response), 'bot'); // Fallback
                    } else {
                        this.addMessage(response, 'bot');
                    }
                },
                error: (xhr) => {
                    this.removeTypingIndicator();
                    console.error('MCNAC Chat Error:', xhr);
                    let errMsg = 'Sorry, something went wrong.';
                    if (xhr.status === 500) errMsg = 'Workflow error (500). Please check n8n logs.';
                    this.addMessage(errMsg, 'bot');
                }
            });
        }
    };

    MCNAC_Chat.init();
});
