tinymce.PluginManager.add('pagemyself', function (editor, url) {
  /** @type {PageMyselfComponent} */
  const component = editor.pagemyselfComponent
  const el = $(editor.targetElm)
  editor.ui.registry.addButton('pagemyself-save-text', {
    text: FramelixLang.get('__framelix_save__'),
    onAction: async function () {
      Framelix.showProgressBar(1)
      await component.apiRequest('textEditorSaveText', { 'id': el.attr('data-id'), 'text': editor.getContent() })
      FramelixToast.success('__framelix_saved__')
      Framelix.showProgressBar(null)
      editor.initialContent = editor.getContent()
      editor.destroy()
      component.enableTextEditor(el)
    }
  })
  editor.ui.registry.addButton('pagemyself-cancel-text', {
    tooltip: FramelixLang.get('__pagemyself_reset_text__'),
    icon: 'rotate-left',
    onAction: function () {
      editor.setContent(editor.initialContent)
      editor.destroy()
      component.enableTextEditor(el)
    }
  })
  editor.ui.registry.addButton('pagemyself-components', {
    text: FramelixLang.get('__pagemyself_editor_components__'),
    tooltip: FramelixLang.get('__pagemyself_editor_components_tooltip__'),
    onAction: async function () {
      const modal = await component.apiRequestInModal('textEditorLayouts')
      modal.bodyContainer.find('.styled-layout').on('click', function () {
        editor.execCommand('mceInsertContent', false, $(this).html().trim())
        modal.destroy()
      })
    }
  })
  return {
    getMetadata: function () {
      return {
        name: FramelixLang.get('__pagemyself_component_text_title__')
      }
    }
  }
})