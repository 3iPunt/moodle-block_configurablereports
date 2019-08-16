/**
 * This module handles the modal to send reports via email.
 *
 * @module      block_configurable_reports/sendemailmodal
 * @package     block_configurable_reports
 * @copyright   2019 Mitxel Moriana @ 3iPunt Moodle Partner <mitxel@tresipunt.com>
 */

define([
    'jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax', 'core/yui'
], function($, Str, ModalFactory, ModalEvents, Fragment, Ajax, Y) {

    var MODAL_TITLE_STRING_KEY = 'mailsendreport';
    var COMPONENT = 'block_configurable_reports';
    var FORM_FRAGMENT_CB = 'send_email_form';
    var FORM_SUBMIT_WS_METHOD = 'block_configurable_reports_send_report_by_email';

    var FormModal = function(contextid, reportid) {
        this.contextid = contextid;
        this.reportid = reportid;
    };

    FormModal.prototype.contextid = -1;

    FormModal.prototype.reportid = -1;

    FormModal.prototype.modal = null;

    FormModal.prototype.init = function(selector) {
        var triggers = $(selector);
        return Str.get_string(MODAL_TITLE_STRING_KEY, COMPONENT).then(function(title) {
            return ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: this.getBody()
            }, triggers);
        }.bind(this)).then(function(modal) {
            this.modal = modal;
            this.modal.setLarge();
            // Reset the modal body every time it is closed.
            this.modal.getRoot().on(ModalEvents.hidden, function() {
                this.modal.setBody(this.getBody());
            }.bind(this));
            // Hide the default form submit buttons on modal shown, we will use the modal buttons instead.
            this.modal.getRoot().on(ModalEvents.shown, function() {
                this.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
            }.bind(this));
            // Catch the modal save event and use it to submit the form inside the modal.
            // Triggering a form submission will give JS validation scripts a chance to check for errors.
            this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
            // Catch the form submit event and use it to submit the form with ajax to our web services.
            this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));
            return this.modal;
        }.bind(this));
    };

    FormModal.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal via fragments.
        var params = {jsonformdata: JSON.stringify(formdata)};
        return Fragment.loadFragment(COMPONENT, FORM_FRAGMENT_CB, this.contextid, params);
    };

    FormModal.prototype.handleFormSubmissionResponse = function() {
        this.modal.hide();
        Y.use('moodle-core-formchangechecker', function() {
            M.core_formchangechecker.reset_form_dirty_state();
        });
        document.location.reload();
    };

    FormModal.prototype.handleFormSubmissionFailure = function(data) {
        // On fail, re-display the form to show possible backend detected errors.
        this.modal.setBody(this.getBody(data));
    };

    FormModal.prototype.submitFormAjax = function(e) {
        e.preventDefault();

        var changeEvent = document.createEvent('HTMLEvents');
        changeEvent.initEvent('change', true, true);

        // Since we prevented default submission, we need to trigger client side validation.
        this.modal.getRoot().find(':input').each(function(index, element) {
            element.dispatchEvent(changeEvent);
        });

        // If invalid fields found, focus on invalid fields and early exit.
        var invalid = $.merge(
            this.modal.getRoot().find('[aria-invalid="true"]'),
            this.modal.getRoot().find('.error')
        );
        if (invalid.length) {
            invalid.first().focus();
            return;
        }

        // If everything is correct, serialize form values and send via ajax to our web service.
        var formData = this.modal.getRoot().find('form').serialize();

        Ajax.call([{
            methodname: FORM_SUBMIT_WS_METHOD,
            args: {
                contextid: this.contextid,
                reportid: this.reportid,
                jsonformdata: JSON.stringify(formData)
            },
            done: this.handleFormSubmissionResponse.bind(this, formData),
            fail: this.handleFormSubmissionFailure.bind(this, formData)
        }]);
    };

    FormModal.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };

    return {
        init: function(selector, contextid, reportid) {
            var formModal = new FormModal(contextid, reportid);
            formModal.init(selector);
        }
    };
});
