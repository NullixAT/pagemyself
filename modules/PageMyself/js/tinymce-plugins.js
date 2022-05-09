tinymce.PluginManager.add('pagemyself', function (editor, url) {
  /** @type {PageMyselfComponent} */
  const component = editor.pagemyselfComponent
  const el = $(editor.targetElm)
  editor.ui.registry.addButton('pagemyself-save-text', {
    text: FramelixLang.get('__framelix_save__'),
    onAction: async function () {
      Framelix.showProgressBar(1)
      const content = editor.getContent({format: 'raw'})
      await component.apiRequest('textEditorSaveText', { 'id': el.attr('data-id'), 'text': content })
      FramelixToast.success('__framelix_saved__')
      Framelix.showProgressBar(null)
      editor.initialContent = content
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
  editor.ui.registry.addMenuButton('pagemyself-templates', {
    text: FramelixLang.get('__pagemyself_editor_templates__'),
    fetch: function (callback) {
      const templates = TinymceTemplates.getTemplates()
      let options = []
      for (let id in templates) {
        const row = templates[id]
        options.push({
          type: 'menuitem',
          text: FramelixLang.get('__pagemyself_editor_templates_type_' + id.toLowerCase() + '__'),
          onAction: async function () {
            let replacements = {}
            let values = null
            if (row.fields) {
              const modalContent = $('<div>')
              const form = new FramelixForm()
              for (let i in row.fields) {
                /** @type {FramelixFormField} */
                const field = row.fields[i]
                field.name = i.toString()
                form.addField(field)
              }
              form.addButton('accept', '__framelix_ok__', 'check', 'success')
              form.render()
              modalContent.append(form.container)
              const formModal = FramelixModal.show({ bodyContent: modalContent, maxWidth: 900 })
              let proceed = false
              form.container.on('click', '.framelix-form-buttons button', async function (ev) {
                ev.stopPropagation()
                ev.stopImmediatePropagation()
                if ((await form.validate()) === true) {
                  proceed = true
                  formModal.destroy()
                }
              })
              await formModal.destroyed
              if (!proceed) return
              values = form.getValues()
              if (values) {
                replacements = values
              }
            }
            let html = await TinymceTemplates.getTemplateHtml(id, values, replacements)
            for (let search in replacements) {
              html = html.replace(new RegExp(FramelixStringUtils.escapeRegex('{' + search + '}'), 'ig'), replacements[search])
            }
            editor.insertContent(html+'<br/>')
          }
        })
      }
      callback(options)
    }
  })
  editor.ui.registry.addMenuItem('block-settings', {
    icon: 'settings',
    text: FramelixLang.get('__pagemyself_editor_templates_settings__'),
    onAction: function () {
      const node = editor.selection.getNode()
      if (!node) return
      const colorPicker = node.closest('[data-color-picker]')
      if (colorPicker) {
        const colorPickerData = colorPicker.dataset.colorPicker.split(',')
        const form = new FramelixForm()
        for (let i in colorPickerData) {
          const row = colorPickerData[i].split('|')
          const field = new FramelixFormFieldColor()
          field.name = i
          field.label = row[0]
          if (row[1] === 'css' && colorPicker.style[row[2]]) field.defaultValue = FramelixColorUtils.cssColorToHex(colorPicker.style[row[2]])
          form.addField(field)
          field.container.on(FramelixFormField.EVENT_CHANGE_USER, function () {
            colorPicker.style[row[2]] = field.getValue()
          })
        }
        form.render()
        const modal = FramelixModal.show({ bodyContent: form.container, maxWidth: 900 })

      }

    }
  })
  editor.ui.registry.addContextMenu('image', {
    update: function (element) {
      return !element.closest('[data-color-picker]') ? '' : 'block-settings'
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