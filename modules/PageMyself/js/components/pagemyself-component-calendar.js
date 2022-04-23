class PageMyselfComponentCalendar extends PageMyselfComponent {

  /**
   * Disable editing of this block
   * @returns {Promise<void>}
   */
  async disableEditing () {
    await super.disableEditing()
    this.container.off('click', 'td[data-date]')
  }

  /**
   * Enable editing of this block
   * @returns {Promise<void>}
   */
  async enableEditing () {
    await super.enableEditing()
    const self = this
    this.container.on('click', 'td[data-date]', function (ev) {
      self.apiRequestInModal('dateInfo', { 'date': $(this).attr('data-date') })
    })

  }

  /**
   * Initialize the block
   * @returns {Promise<void>}
   */
  async init () {
    await super.init()
    const tableContainer = this.container.find('.calendar-table')
    const self = this
    this.container.on('click', '.calendar-month-select [data-action="gettable"]', async function (ev) {
      ev.preventDefault()
      tableContainer.toggleClass('framelix-pulse', true)
      window.history.pushState(null, null, this.href)
      const result = await self.apiRequest('getTable', { 'date': $(this).attr('data-date') })
      tableContainer.toggleClass('framelix-pulse', false)
      tableContainer.html(result)
    })
  }

}