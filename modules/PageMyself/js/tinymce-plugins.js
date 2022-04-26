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
            const templateContainer = $('<div>').html(row.html)
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
              const formModal = FramelixModal.show({ bodyContent: modalContent })
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
            await TinymceTemplates.onBeforeInsert(id, templateContainer, values)
            let html = templateContainer.html()
            for (let search in replacements) {
              html = html.replace(new RegExp(FramelixStringUtils.escapeRegex('{' + search + '}'), 'ig'), replacements[search])
            }
            editor.insertContent(html)
          }
        })
      }
      callback(options)
    }
  })
  editor.ui.registry.addNestedMenuItem('pagemyself-templates', {
    text: FramelixLang.get('__pagemyself_editor_templates__'),
    tooltip: FramelixLang.get('__pagemyself_editor_templates_tooltip__'),
    getSubmenuItems: function () {
      const templatesInstance = window.top.eval('PageMyselfPageEditorTinymceTemplates')
      const templates = templatesInstance.getTemplates()
      let options = []
      for (let id in templates) {
        const row = templates[id]
        options.push({
          type: 'menuitem',
          text: row.title,
          onAction: async function () {
            let replacements = {}
            const templateContainer = $('<div>').html(row.html)
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
              const formModal = FramelixModal.show({ bodyContent: modalContent })
              let proceed = false
              form.container.on('click', '.framelix-form-buttons button', function () {
                proceed = true
                formModal.destroy()
              })
              await formModal.destroyed
              if (!proceed) return
              const values = form.getValues()
              if (values) {
                replacements = values
              }
            }
            await templatesInstance.onBeforeInsert(id, templatesInstance)
            let html = templateContainer.html()
            for (let search in replacements) {
              html = html.replace(new RegExp(FramelixStringUtils.escapeRegex('{' + search + '}'), 'ig'), replacements[search])
            }
            editor.insertContent(html + '<br/>')
          }
        })
      }
      return options
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