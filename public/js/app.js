function createFullBlockLoader(text) {
    const loaderElement = document.createElement('div')
    loaderElement.className = 'fullblock-loader'

    const loaderInner = document.createElement('div')
    loaderInner.className = 'loader'

    const lightElement = document.createElement('light')
    lightElement.textContent = text

    const span1 = document.createElement('span')
    span1.textContent = '.'

    const span2 = document.createElement('span')
    span2.textContent = '.'

    const span3 = document.createElement('span')
    span3.textContent = '.'

    loaderInner.appendChild(lightElement)
    loaderInner.appendChild(span1)
    loaderInner.appendChild(span2)
    loaderInner.appendChild(span3)

    loaderElement.appendChild(loaderInner)

    return loaderElement
}