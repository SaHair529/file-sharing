let selectedFiles = []
let selectedFilesInGB = 0

document.addEventListener('click', function (e) {
    const notClose = e.target.dataset.notClose
    if (!e.target.classList.contains('closable') && !e.target.closest('.closable')) {
        document.querySelectorAll('.closable').forEach(element => {
            if (!element.classList.contains(notClose)) {
                element.hidden = true
            }
        })
    }
})

document.querySelector('.btn-info-open').addEventListener('click', function () {
    document.querySelector('.info').hidden = false
})

document.querySelector('#file').addEventListener('change', function (e) {
    const files = e.target.files
    const $filesGalleryBody = document.querySelector('.files-gallery__body')
    const $uploadFilesBtn = document.querySelector('.upload-files-btn')

    $filesGalleryBody.innerHTML = ''

    selectedFiles = Array.from(files)
    
    selectedFiles.forEach((file, i) => {
        const $fileItem = document.createElement('div')
        $fileItem.classList.add('file-item')
        $fileItem.title = file.name
        $fileItem.innerHTML = `
            <button class="file-item__remove" onclick="removeFile(${i})">×</button>
            <div class="file-item__image">${getFileEmoji(file.name)}</div>
            <div class="file-item__name">${file.name}</div>
        `
        $filesGalleryBody.appendChild($fileItem)
    })

    const selectedFilesSize = selectedFiles.reduce((total, file) => total + file.size, 0)
    selectedFilesInGB = (selectedFilesSize / (1024 * 1024 * 1024)).toFixed(2)
    document.querySelector('.used-memory-value').textContent = selectedFilesInGB + ' GB'

    if (selectedFiles.length) {
        document.querySelector('.files-gallery').hidden = false
        $uploadFilesBtn.hidden = false
    }
    else {
        document.querySelector('.files-gallery').hidden = true
        $uploadFilesBtn.hidden = true
    }
})

document.querySelector('.upload-files-btn').addEventListener('click', function (e) {
    e.target.disabled = true
    document.querySelector('.files-gallery__body').replaceChildren(createFullBlockLoader('Файлы загружаются'))

    const url = document.querySelector('form').getAttribute('action')
    const formData = new FormData()
    selectedFiles.forEach(file => {
        formData.append('files[]', file)
    })

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            response.json().then(responseData => {
                selectedFiles = []
                document.querySelector('#file').value = ''
                document.querySelector('.files-gallery__body').innerHTML = ''
                document.querySelector('.files-gallery').hidden = true
                document.querySelector('.upload-files-btn').hidden = true
                e.target.disabled = false

                document.querySelector('.upload-result').hidden = false
                document.querySelector('.uploaded-page-link').textContent = responseData.link
            })
        } 
        else {
            alert('Failed to upload files.')
        }
    })
})

document.querySelector('.close-upload-result').addEventListener('click', function () {
    const $uploadResult = document.querySelector('.upload-result')
    $uploadResult.hidden = true
    
    document.querySelector('.uploaded-page-link').textContent = ''
})

document.querySelector('.uploaded-page-link').addEventListener('click', function () {
    const link = this.textContent.trim()
    navigator.clipboard.writeText(link).then(() => {
        document.querySelector('.buffer-copy-notification').hidden = false
        setTimeout(() => {
            document.querySelector('.buffer-copy-notification').hidden = true
        }, 2000)
    })
})

function removeFile(index) {
    selectedFiles.splice(index, 1)

    const $filesGalleryBody = document.querySelector('.files-gallery__body')
    const $uploadFilesBtn = document.querySelector('.upload-files-btn')
    $filesGalleryBody.innerHTML = ''

    selectedFiles.forEach((file, index) => {
        const $fileItem = document.createElement('div')
        $fileItem.classList.add('file-item')
        $fileItem.title = file.name
        $fileItem.innerHTML = `
            <button class="file-item__remove" onclick="removeFile(${index})">×</button>
            <div class="file-item__image">${getFileEmoji(file.name)}</div>
            <div class="file-item__name">${file.name}</div>
        `
        $filesGalleryBody.appendChild($fileItem);
    })

    if (selectedFiles.length === 0) {
        document.querySelector('.files-gallery').hidden = true
        $uploadFilesBtn.hidden = true
    }
}

function getFileEmoji(fileName) {
    const fileExtension = fileName.split('.').pop().toLowerCase()

    switch (fileExtension) {
        case 'txt':
        case 'md':
        case 'log':
            return '📝'

        case 'doc':
        case 'docx':
        case 'pdf':
            return '📄'

        case 'xls':
        case 'xlsx':
        case 'csv':
            return '📊'

        case 'ppt':
        case 'pptx':
            return '📑'

        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'svg':
            return '🖼️'

        case 'mp4':
        case 'avi':
        case 'mov':
        case 'mkv':
            return '🎥'

        case 'mp3':
        case 'wav':
        case 'ogg':
            return '🎵'

        case 'zip':
        case 'rar':
        case '7z':
        case 'tar':
        case 'gz':
            return '📦'

        case 'js':
        case 'py':
        case 'html':
        case 'css':
        case 'java':
        case 'c':
        case 'cpp':
        case 'cs':
        case 'go':
        case 'php':
        case 'rb':
        case 'swift':
            return '💻'

        case 'exe':
        case 'sh':
        case 'bat':
            return '⚙️'

        default:
            return '❓'
    }
}