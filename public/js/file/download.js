const token = document.querySelector('#token').value

document.querySelectorAll('.file-item').forEach(item => {
    item.addEventListener('click', (event) => {
        const fileName = event.target.closest('.file-item').dataset.filename
        fetch(`/download/${token}/${fileName}`)
            .then(response => {
                if (response.ok) {
                    return response.blob()
                } 
                else {
                    throw new Error('Failed to download file.')
                }
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob)
                const a = document.createElement('a')
                a.href = url
                a.download = fileName
                document.body.appendChild(a)
                a.click()
                a.remove()
                window.URL.revokeObjectURL(url)
            })
            .catch(error => {
                alert(error.message)
            })
    })
})