class TinymceTemplates {

  static LABEL_COLOR_BG = '__pagemyself_editor_templates_colorpicker_bg__'
  static LABEL_COLOR_TEXT = '__pagemyself_editor_templates_colorpicker_text__'

  /**
   * Get templates
   * @returns {Object<string, {html: string, title: string}>}
   */
  static getTemplates () {
    const defaultText = FramelixLang.get('__pagemyself_editor_templates_edit_text__')
    return {
      'alert': {
        'html': `<br/><div class="framelix-alert framelix-alert-customcolor" style="--color-custom-bg:{0}; --color-custom-text:{1};">${defaultText}</div><br/>`,
        'fields': [
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_BG,
            'defaultValue': '#009dff',
            required: true
          }),
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_TEXT,
            'defaultValue': '#fff',
            required: true
          })
        ]
      },
      'buttonLink': {
        'html': `<a class="framelix-button framelix-button-customcolor" style="--color-custom-bg:{2}; --color-custom-text:{3};" href="{0}" target="_blank">{1}</a>`,
        'fields': [
          Object.assign(new FramelixFormFieldText(), {
            'label': 'URL',
            required: true
          }),
          Object.assign(new FramelixFormFieldText(), {
            'label': 'Text',
            required: true
          }),
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_BG,
            'defaultValue': '#009dff',
            required: true
          }),
          Object.assign(new FramelixFormFieldColor(), {
            'label': this.LABEL_COLOR_TEXT,
            'defaultValue': '#fff',
            required: true
          })
        ]
      },
      'columns': {
        'html': ``,
        'fields': [
          Object.assign(new FramelixFormFieldNumber(), {
            'label': '__pagemyself_editor_templates_type_columns_number__',
            required: true,
            max: 5,
            defaultValue: 2
          })
        ]
      }
    }
  }

  /**
   * On before insert into the tinymce editor
   * Use this to ask user for some input or stuff like that that cant be handled with default fields
   * Manipulate template container to your needs
   * Do not bind events on this container, they will not be fired
   * @param {string} id The template id
   * @param {Cash} templateContainer
   * @param {Object=} formValues
   */
  static async onBeforeInsert (id, templateContainer, formValues) {
    switch (id) {
      case 'columns':
        const columns = parseInt(formValues[0])
        const container = $(`<div class="pagemyself-columns" data-columns="${columns}"></div>`)
        for (let i = 1; i <= columns; i++) {
          container.append(`<div class="pagemyself-column">Your text here</div>`)
        }
        templateContainer.html(container)
        break
    }
  }
}