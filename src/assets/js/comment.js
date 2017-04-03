/**
 * Comment plugin
 */
(function ($) {

    $.fn.comment = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.comment');
            return false;
        }
    };

    // Default settings
    var defaults = {
        toolsSelector: '.comment-action-buttons',
        formSelector: '#comment-form',
        formContainerSelector: '.comment-form-container',
        contentSelector: '.comment-body',
        cancelReplyBtnSelector: '#cancel-reply',
        pjaxContainerId: '#comment-pjax-container',
        pjaxSettings: {
            timeout: 10000,
            scrollTo: false,
            url: window.location.href
        },
        submitBtnText: 'Comment',
        submitBtnLoadingText: 'Loading...'
    };

    var commentData = {};

    ///[v0.0.14 (CHG# vote)]
    var placeholder = $("textarea[name='CommentModel[content]']").attr('placeholder');

    // Methods
    var methods = {
        init: function (options) {
            return this.each(function () {

                ///[v0.1.0 (ADD# pjaxTimeout)]Should set to `1` to equivalent to disable pjax when using ueditor (which use iframe!)
                defaults.pjaxSettings.timeout = $(options.pjaxContainerId).data('pjax-timeout');

                var $comment = $(this);
                var settings = $.extend({}, defaults, options || {});
                var id = $comment.attr('id');
                if (commentData[id] === undefined) {
                    commentData[id] = {};
                } else {
                    return;
                }
                commentData[id] = $.extend(commentData[id], {settings: settings});

                var formSelector = commentData[id].settings.formSelector;
                var eventParams = {formSelector: formSelector, wrapperSelector: id};

                $comment.on('beforeSubmit.comment', formSelector, eventParams, createComment);
                $comment.on('click.comment', '[data-action="reply"]', eventParams, reply);
                $comment.on('click.comment', '[data-action="cancel-reply"]', eventParams, cancelReply);
                $comment.on('click.comment', '[data-action="delete"]', eventParams, deleteComment);

                ///[v0.0.14 (CHG# vote)]
                $comment.on('click.comment', '[data-action="vote"]', eventParams, voteComment);

            });
        },
        data: function () {
            var id = $(this).attr('id');
            return commentData[id];
        }
    };


    /**
     * Create a comment
     * @returns {boolean}
     */
    function createComment(event) {
        var $commentForm = $(this);
        var settings = commentData[event.data.wrapperSelector].settings;

        ///[FIX# comment pagination url pjax issue]
        // var pjaxSettings = $.extend({container: settings.pjaxContainerId}, settings.pjaxSettings);
        var pjaxSettings = $.extend(settings.pjaxSettings, {container: settings.pjaxContainerId, url: window.location.href});

        var formData = $commentForm.serializeArray();
        formData.push({'name': 'CommentModel[related_url]', 'value': getCurrentUrl()});
        // disable submit button
        $commentForm.find(':submit').prop('disabled', true).text(settings.submitBtnLoadingText);
        // creating a comment and errors handling
        $.post($commentForm.attr('action'), formData, function (data) {
            if (data.status == 'success') {
                $.pjax(pjaxSettings);
            }
            // errors handling
            else {
                if (data.hasOwnProperty('errors')) {
                    $commentForm.yiiActiveForm('updateMessages', data.errors, true);
                }
                else {
                    $commentForm.yiiActiveForm('updateAttribute', 'commentmodel-content', [data.message]);
                }
                // enable submit button
                $commentForm.find(':submit').prop('disabled', false).text(settings.submitBtnText);
            }
        }).fail(function (xhr, status, error) {
            alert(error);
            $.pjax(pjaxSettings);
        });

        return false;
    }

    /**
     * Reply to comment
     * @param event
     */
    function reply(event) {
        var $this = $(this);
        var $commentForm = $(event.data.formSelector);
        var settings = commentData[event.data.wrapperSelector].settings;
        var parentCommentSelector = $this.parents('[data-comment-content-id="' + $this.data('comment-id') + '"]');
        // append the comment form inside particular comment container
        $commentForm.appendTo(parentCommentSelector);
        $commentForm.find('[data-comment="parent-id"]').val($this.data('comment-id'));
        $commentForm.find(settings.cancelReplyBtnSelector).show();

        ///[v0.0.11 (ADD# placeholder @authorName)]
        var authorName = $(parentCommentSelector).find('.comment-author-name span').html();
        $commentForm.find("textarea[name='CommentModel[content]']").attr('placeholder', '@' + authorName);

        return false;
    }

    /**
     * Cancel reply
     * @param event
     */
    function cancelReply(event) {
        var $commentForm = $(event.data.formSelector);
        var settings = commentData[event.data.wrapperSelector].settings;
        var formContainer = $(settings.pjaxContainerId).find(settings.formContainerSelector);
        // prepend the comment form to `formContainer`
        $commentForm.find(settings.cancelReplyBtnSelector).hide();
        $commentForm.prependTo(formContainer);
        $commentForm.find('[data-comment="parent-id"]').val(null);

        ///[v0.0.14 (CHG# vote)]
        $commentForm.find("textarea[name='CommentModel[content]']").attr('placeholder', placeholder);
        
        return false;
    }

    /**
     * Delete a comment
     * @param event
     */
    function deleteComment(event) {
        var $this = $(this);
        var settings = commentData[event.data.wrapperSelector].settings;

        $.ajax({
            url: $this.data('url'),
            type: 'DELETE',
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result, status, xhr) {
                $this.parents('[data-comment-content-id="' + $this.data('comment-id') + '"]').find(settings.contentSelector).text(result);
                $this.parents(settings.toolsSelector).remove();
            }
        });

        return false;
    }

    ///[v0.0.14 (CHG# vote)]
    /**
     * Delete a comment
     * @param event
     */
    function voteComment(event) {
        var $this = $(this);
        var settings = commentData[event.data.wrapperSelector].settings;

        ///[FIX# comment pagination url pjax issue]
        // var pjaxSettings = $.extend({container: settings.pjaxContainerId}, settings.pjaxSettings);
        var pjaxSettings = $.extend(settings.pjaxSettings, {container: settings.pjaxContainerId, url: window.location.href});

        var url = $this.data('url');
        var value = $this.data('value');
        var comment_id = $this.data('comment-id');
        $.ajax({
            url: url,
            type: 'POST',
            data: {id: comment_id, vote: value},
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result, status, xhr) {

                $.pjax(pjaxSettings);
            }
        });

        return false;
    }

    /**
     * Get current url without `hostname`
     * @returns {string}
     */
    function getCurrentUrl() {
        return window.location.pathname + window.location.search;
    }

})(window.jQuery);
