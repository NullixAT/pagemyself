class CalendarPageBlocksCalendar extends MyselfPageBlocks {
  /**
   * Init block
   */
  initBlock () {
    this.blockContainer.on(FramelixFormField.EVENT_CHANGE, '.calendar-pageblocks-calendar-month-select', function () {
      const field = FramelixFormField.getFieldByName($(this), 'month')
      window.location.href = field.getValue()
    })
    this.blockContainer.on('click', 'td[data-modal]', function (ev) {
      FramelixModal.callPhpMethod($(this).attr('data-modal'))
    })
    const tableContainer = this.blockContainer.find('.calendar-pageblocks-calendar-table')
    this.blockContainer.on('click', '.calendar-pageblocks-calendar-month-select a[data-jscall]', async function (ev) {
      ev.preventDefault()
      tableContainer.toggleClass('framelix-pulse', true)
      window.history.pushState(null, null, this.href)
      const result = await FramelixApi.callPhpMethod($(this).attr('data-jscall'))
      tableContainer.toggleClass('framelix-pulse', false)
      tableContainer.html(result)
    })
  }
}