define(function(require, exports, module) {
    var Notify = require('common/bootstrap-notify');
    exports.run = function() {
        $('.js-attachment-list').on('click',  '.js-attachment-delete' ,function() {
            var $this = $(this);
            var attachment_remove = confirm("确定要删除附件吗?")
            if (attachment_remove) {
                $.post($this.data('url'), function(result) {

                }).done(function(result) {
                    if (result.msg == 'ok') {
                        Notify.success('附件已删除');
                        $this.parent().remove();
                        $('.js-upload-file').removeClass('hidden');
                    } else {
                        Notify.danger('附件删除失败,请稍后再试');
                    }
                }).fail(function(ajaxFailed) {
                    Notify.danger('附件删除失败,请稍稍后再试');
                })
            }
        })
    }
});