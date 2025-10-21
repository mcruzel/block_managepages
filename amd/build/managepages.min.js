/**
 * JavaScript module for block_managepages.
 *
 * @module     block_managepages/managepages
 * @copyright  2025 Maxime Cruzel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    'use strict';

    /**
     * Module instance.
     */
    var ManagePages = function() {
        this.form = null;
        this.courseid = null;
        this.sesskey = null;
        this.currentPageId = null;
        this.strings = {};
    };

    /**
     * Initialize the module.
     *
     * @param {Number} courseid Course ID
     * @param {String} sesskey Session key
     */
    ManagePages.prototype.init = function(courseid, sesskey) {
        var self = this;
        self.courseid = courseid;
        self.sesskey = sesskey;
        self.form = $('#export-form');

        if (self.form.length === 0) {
            return;
        }

        // Load required strings
        Str.get_strings([
            {key: 'error:nopagesselected', component: 'block_managepages'},
            {key: 'edit_page_title', component: 'block_managepages'},
            {key: 'error:loadpage', component: 'block_managepages'},
            {key: 'error:savepage', component: 'block_managepages'},
            {key: 'success:pagesaved', component: 'block_managepages'}
        ]).then(function(strings) {
            self.strings = {
                nopagesselected: strings[0],
                editPageTitle: strings[1],
                errorLoad: strings[2],
                errorSave: strings[3],
                successSave: strings[4]
            };
            return true;
        }).catch(Notification.exception);

        // Bind event handlers
        self.bindDownloadAll();
        self.bindCopyToClipboard();
        self.bindEditButtons();
        self.bindModalActions();
    };

    /**
     * Bind the "Download all" button.
     */
    ManagePages.prototype.bindDownloadAll = function() {
        var self = this;
        $('#download-all-btn').on('click', function() {
            $('input[name="page_ids[]"]').prop('checked', true);
            self.form.submit();
        });
    };

    /**
     * Bind the "Copy to clipboard" button.
     */
    ManagePages.prototype.bindCopyToClipboard = function() {
        var self = this;
        $('#copy-markdown-btn').on('click', function(e) {
            e.preventDefault();
            self.copyMarkdownToClipboard();
        });
    };

    /**
     * Copy markdown content to clipboard.
     */
    ManagePages.prototype.copyMarkdownToClipboard = function() {
        var self = this;
        var selected = [];

        $('input[name="page_ids[]"]:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            Notification.alert('', self.strings.nopagesselected);
            return;
        }

        var params = {
            sesskey: self.sesskey,
            courseid: self.courseid,
            ajax: 1,
            'page_ids': selected
        };

        $.ajax({
            url: M.cfg.wwwroot + '/blocks/managepages/export.php',
            type: 'GET',
            data: params,
            dataType: 'json'
        }).done(function(data) {
            if (data.markdown) {
                self.writeToClipboard(data.markdown);
            } else {
                Notification.alert('', 'Error: Invalid response from server');
            }
        }).fail(function(xhr) {
            var message = 'Error fetching markdown content';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                message = xhr.responseJSON.error;
            }
            Notification.alert('', message);
        });
    };

    /**
     * Write text to clipboard.
     *
     * @param {String} text Text to copy
     */
    ManagePages.prototype.writeToClipboard = function(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                Notification.addNotification({
                    message: 'Content copied to clipboard!',
                    type: 'success'
                });
            }).catch(function() {
                Notification.alert('', 'Failed to copy to clipboard');
            });
        } else {
            // Fallback for older browsers
            var textarea = $('#markdown-content');
            textarea.val(text).show();
            textarea[0].select();
            try {
                document.execCommand('copy');
                textarea.hide();
                Notification.addNotification({
                    message: 'Content copied to clipboard!',
                    type: 'success'
                });
            } catch (err) {
                textarea.hide();
                Notification.alert('', 'Failed to copy to clipboard');
            }
        }
    };

    /**
     * Bind edit page buttons.
     */
    ManagePages.prototype.bindEditButtons = function() {
        var self = this;
        $('.edit-page-btn').on('click', function() {
            var pageid = $(this).data('pageid');
            self.openEditModal(pageid);
        });
    };

    /**
     * Open the edit modal for a page.
     *
     * @param {Number} pageid Page ID
     */
    ManagePages.prototype.openEditModal = function(pageid) {
        var self = this;
        self.currentPageId = pageid;

        $.ajax({
            url: M.cfg.wwwroot + '/blocks/managepages/export.php',
            type: 'GET',
            data: {
                ajax: 2,
                sesskey: self.sesskey,
                courseid: self.courseid,
                pageid: pageid
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.content && data.name) {
                var title = self.strings.editPageTitle.replace('{$a}', data.name);
                $('#block-managepages-modal-title').text(title);
                $('#block-managepages-modal-content').val(data.content);
                $('#block-managepages-modal').css('display', 'flex');
            } else {
                Notification.alert('', self.strings.errorLoad);
            }
        }).fail(function() {
            Notification.alert('', self.strings.errorLoad);
        });
    };

    /**
     * Bind modal actions (save/cancel).
     */
    ManagePages.prototype.bindModalActions = function() {
        var self = this;

        $('#block-managepages-modal-cancel').on('click', function() {
            self.closeModal();
        });

        $('#block-managepages-modal-save').on('click', function() {
            self.savePageContent();
        });
    };

    /**
     * Close the edit modal.
     */
    ManagePages.prototype.closeModal = function() {
        $('#block-managepages-modal').hide();
        this.currentPageId = null;
    };

    /**
     * Save the edited page content.
     */
    ManagePages.prototype.savePageContent = function() {
        var self = this;

        if (!self.currentPageId) {
            return;
        }

        var content = $('#block-managepages-modal-content').val();

        $.ajax({
            url: M.cfg.wwwroot + '/blocks/managepages/export.php',
            type: 'POST',
            data: {
                ajax: 3,
                sesskey: self.sesskey,
                courseid: self.courseid,
                pageid: self.currentPageId,
                content: content
            },
            dataType: 'json'
        }).done(function(data) {
            if (data.success) {
                self.closeModal();
                Notification.addNotification({
                    message: self.strings.successSave,
                    type: 'success'
                });
            } else {
                Notification.alert('', self.strings.errorSave);
            }
        }).fail(function() {
            Notification.alert('', self.strings.errorSave);
        });
    };

    return {
        /**
         * Initialize the module.
         *
         * @param {Number} courseid Course ID
         * @param {String} sesskey Session key
         */
        init: function(courseid, sesskey) {
            var managepages = new ManagePages();
            managepages.init(courseid, sesskey);
        }
    };
});
