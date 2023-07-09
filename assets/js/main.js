// Main JS file

// Image lightbox
let modal = document.getElementById('imageModal')
let modalImage = document.getElementById('modalImage')

modal.addEventListener('show.bs.modal', function (event) {
    let imageLink = event.relatedTarget
    let src = imageLink.getAttribute('href')
    modalImage.setAttribute('src', src)
})
