/**
 * Myself Edit Class
 */
class MyselfEdit {

    /**
     * Edit config
     * @type {Object<string, *>}
     */
    static config

    /**
     * The page id in the edit frame
     * @type {number}
     */
    static framePageId = 0

    /**
     * Init late
     */
    static initLate() {
        const editFrame = $('.myself-edit-frame iframe')
        let editFrameWindow = editFrame[0].contentWindow
        let editFrameDoc = $(editFrameWindow.document)
        const html = $("html")
        html.attr("data-nav-status", editFrame.width() < 500 ? 'closed' : 'opened')

        editFrame.on('load', function () {
            editFrameWindow = editFrame[0].contentWindow
            editFrameDoc = $(editFrameWindow.document)
            const editFrameHtml = editFrameDoc[0].querySelector('html')
            editFrameHtml.setAttribute('data-edit-frame', '1')
            MyselfEdit.framePageId = parseInt(editFrameHtml.getAttribute('data-page'))
            let url = new URL(editFrameWindow.location.href)
            url.searchParams.set('editMode', '1')
            window.history.pushState(null, null, url)
            MyselfEdit.bindLiveEditableWysiwyg(editFrameWindow)
            editFrameDoc.on("contextmenu", function (ev) {
                if (!ev.ctrlKey) return
                const layoutRow = $(ev.target).closest(".myself-block-layout-row")
                const layoutColumn = $(ev.target).closest(".myself-block-layout-row-column")
                const pageBlock = $(ev.target).closest(".myself-page-block")
                if (!layoutRow.length && !layoutColumn.length && !pageBlock.length) return
                ev.stopPropagation()
                ev.preventDefault()
                let action = 'row-settings'
                if (!layoutRow.length || layoutColumn.length) action = 'column-settings'
                FramelixModal.callPhpMethod(MyselfEdit.config.blockLayoutApiUrl, {
                    'pageId': MyselfEdit.framePageId,
                    'action': action,
                    'rowId': layoutRow.attr("data-row-id"),
                    'columnId': layoutRow.attr("data-column-id"),
                    'pageBlockId': pageBlock.attr("data-id")
                }, {maximized: true}).then(function (modal) {
                    modal.contentContainer.on(FramelixForm.EVENT_SUBMITTED, function () {
                        editFrameWindow.location.reload()
                        modal.destroy()
                    })
                })
            })
        })
        $(document).on('click', '.myself-edit-frame-nav-toggle', async function (ev) {
            html.attr("data-nav-status", html.attr("data-nav-status") === 'opened' ? 'closed' : 'opened')
        })
        $(document).on('click', '.myself-open-website-settings', async function (ev) {
            const modal = await FramelixModal.request('post', MyselfEdit.config.websiteSettingsEditUrl, {'pageId': MyselfEdit.framePageId}, null, false, null, {maximized: true})
            modal.contentContainer.addClass('myself-edit-font')
            modal.destroyed.then(function () {
                location.reload()
            })
        })
        $(document).on('click', '.myself-delete-page-block', async function () {
            if (!(await FramelixModal.confirm('__framelix_sure__').confirmed)) return
            const urlParams = {
                'action': null,
                'pageId': null,
                'pageBlockId': null,
                'pageBlockClass': null
            }
            for (let k in urlParams) {
                urlParams[k] = this.dataset[k] || null
            }
            await FramelixRequest.request('post', MyselfEdit.config.pageBlockEditUrl, {
                'action': 'delete',
                'pageBlockId': $(this).attr('data-page-block-id')
            })
            location.reload()
        })
        $(document).on('click', '.myself-open-layout-block-editor', async function () {
            const instance = await MyselfBlockLayoutEditor.open()
            instance.modal.destroyed.then(function () {
                editFrameWindow.location.reload()
            })
        })
        $(document).on(FramelixForm.EVENT_SUBMITTED, '.myself-page-block-edit-tabs', function (ev) {
            const target = $(ev.target)
            const tabContent = target.closest('.framelix-tab-content')
            const tabButton = tabContent.closest('.framelix-tabs').children('.framelix-tab-buttons').children('.framelix-tab-button[data-id=\'' + tabContent.attr('data-id') + '\']')
            tabButton.find('.myself-tab-edited').remove()
        })
    }

    /**
     * Bind live editable wysiwyg
     * @param {Window} frame
     */
    static async bindLiveEditableWysiwyg(frame) {
        const frameDoc = frame.document
        const topFrame = frame.top
        if (!frameDoc.myselfLiveEditableText) {
            frameDoc.myselfLiveEditableText = new Map()
        }
        const mediaBrowser = new MyselfFormFieldMediaBrowser()
        await frame.eval('FramelixDom').includeResource(MyselfEdit.config.tinymceUrl, 'tinymce')
        $(frameDoc).on('focusin', '.myself-live-editable-wysiwyg:not(.mce-content-body)', async function () {
            const container = frame.$(this)
            container.removeAttr('title').removeAttr('data-tooltip')
            frame.eval('FramelixPopup').destroyTooltips()
            const originalContent = container.html()
            let font_formats = ''
            for (let key in Myself.defaultFonts) {
                const row = Myself.defaultFonts[key]
                font_formats += key + '=' + row.name + ';'
            }
            for (let key in Myself.customFonts) {
                const row = Myself.customFonts[key]
                font_formats += row.name + '=' + row.name + ';'
            }
            let fontSizes = []
            for (let i = 0.6; i <= 8; i += 0.2) {
                fontSizes.push(i.toFixed(1) + 'rem')
            }
            frame.tinymce.init({
                'font_formats': font_formats,
                fontsize_formats: fontSizes.join(' '),
                language: ['en', 'de'].indexOf(FramelixLang.lang) > -1 ? FramelixLang.lang : 'en',
                target: container[0],
                menubar: false,
                inline: true,
                plugins: 'image link media table hr advlist lists code noneditable',
                external_plugins: {
                    myself: FramelixConfig.compiledFileUrls['Myself']['js']['tinymce']
                },
                style_formats: [
                    {
                        title: 'Button Link',
                        inline: 'a',
                        classes: 'framelix-button framelix-button-primary',
                        attributes: {href: '#'}
                    }
                ],
                // The following option is used to append style formats rather than overwrite the default style formats.
                style_formats_merge: true,
                file_picker_callback: async function (callback, value, meta) {
                    if (!mediaBrowser.signedGetBrowserUrl) {
                        mediaBrowser.signedGetBrowserUrl = (await FramelixRequest.request('get', MyselfEdit.config.pageBlockEditUrl + '?action=getmediabrowserurl').getJson()).content
                    }
                    await mediaBrowser.render()
                    mediaBrowser.openBrowserBtn.trigger('click')
                    mediaBrowser.modal.destroyed.then(function () {
                        let url = null
                        if (!mediaBrowser.getValue()) {
                            callback('')
                            return
                        }
                        const entry = mediaBrowser.selectedEntriesContainer.children().first()
                        url = entry.attr('data-url')
                        url = url.replace(/\?t=[0-9]+/g, '')
                        callback(url)
                    })
                },
                toolbar: 'myself-save-text myself-cancel-text myself-jump-mark myself-icons | undo redo | bold italic underline strikethrough | fontselect fontsizeselect styleselect lineheight | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist checklist | table | forecolor backcolor removeformat | image media pageembed link | code',
                powerpaste_word_import: 'clean',
                powerpaste_html_import: 'clean',
                setup: function (editor) {
                    //editor.formatter.register('Button Link', { inline: 'a', classes: 'framelix-button framelix-button-primary' })
                    editor.myself = {
                        'container': container,
                        'originalContent': originalContent,
                        'pageBlockEditUrl': topFrame.eval('MyselfEdit').config.pageBlockEditUrl
                    }
                    editor.MyselfEdit = MyselfEdit
                }
            })
        })
    }
}

FramelixInit.late.push(MyselfEdit.initLate)