jQuery(document).ready(function($) {
    $('.neoalbum-container').each(function() {
        var $container = $(this);
        var albumId = $container.data('album-id');
        var width = $container.data('width');
        var height = $container.data('height');
        var soundEnabled = $container.data('sound');
        var speed = $container.data('speed');
        var hasPassword = $container.data('has-password') === '1';
        var lockImages = $container.data('lock-images') === '1';
        var preventScreenshot = $container.data('prevent-screenshot') === '1';

        var $book = $container.find('.neoalbum-book');
        var $leftPage = $container.find('.left-page');
        var $rightPage = $container.find('.right-page');
        var $prevBtn = $container.find('.neoalbum-prev-btn');
        var $nextBtn = $container.find('.neoalbum-next-btn');
        var $fullscreenBtn = $container.find('.neoalbum-fullscreen-btn');
        var $zoomIcon = $container.find('.neoalbum-fullscreen-zoom-icon');
        var $pageIndicator = $container.find('.neoalbum-page-indicator');
        var $passwordInput = $container.find('.neoalbum-password-input');
        var $submitPassword = $container.find('.neoalbum-submit-password');
        var $passwordError = $container.find('.neoalbum-password-error');
        var $bookWrapper = $container.find('.neoalbum-book-wrapper');

        var images = [];
        $container.find('.neoalbum-image-url').each(function() {
            images.push($(this).data('url'));
        });

        images.sort(function(a, b) {
            var getNum = function(url) {
                var match = url.match(/(\d+)\.(jpg|jpeg|png|gif|webp)$/i);
                return match ? parseInt(match[1]) : 999;
            };
            return getNum(a) - getNum(b);
        });

        var currentSpread = 0;
        var totalSpreads = Math.max(1, Math.ceil(images.length / 2));
        var totalPages = images.length;
        var isAnimating = false;
        var isFullscreen = false;

        // Normal view state
        var normalZoom = 1;
        var normalPanX = 0;
        var normalPanY = 0;

        // Fullscreen view state
        var fullscreenCurrentPage = 0;
        var fullscreenZoom = 1;
        var fullscreenPanX = 0;
        var fullscreenPanY = 0;

        // General state
        var zoomLevel = 1;
        var panX = 0;
        var panY = 0;
        var maxZoom = 5;
        var minZoom = 1;

        // Panning state
        var isDragging = false;
        var startX, startY, startPanX, startPanY;

        // Pinch to zoom
        var lastDist = 0;
        var lastZoom = 1;

        // Double tap
        var lastTap = 0;

        // Fullscreen elements
        var $fullscreenPage = null;
        var $fullscreenImg = null;

        // Set initial book size with mobile responsiveness
        function setBookSize() {
            if (window.innerWidth <= 768) {
                // Mobile: use 100% width with A4 aspect ratio
                $book.css({
                    'width': '100%',
                    'height': 'auto'
                });
            } else {
                // Desktop: use original size
                $book.css({
                    'width': width,
                    'height': height
                });
            }
        }
        
        setBookSize();
        $(window).on('resize', setBookSize);

        if (lockImages) {
            $container.find('.neoalbum-page-image').addClass('protected');
            $container.on('contextmenu dragstart selectstart', function(e) {
                e.preventDefault();
                return false;
            });
        }

        if (preventScreenshot) {
            $container.addClass('screenshot-protected');
            preventScreenshotMethods();
        }

        function preventScreenshotMethods() {
            // Block screenshot keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (e.keyCode === 44 || 
                    (e.ctrlKey && (e.keyCode === 67 || e.keyCode === 80 || e.keyCode === 83 || e.keyCode === 86)) ||
                    (e.ctrlKey && e.shiftKey && e.keyCode === 83) ||
                    (e.metaKey && (e.keyCode === 67 || e.keyCode === 83 || e.keyCode === 86)) ||
                    (e.keyCode === 118 || (e.shiftKey && e.keyCode === 49))) {
                    e.preventDefault();
                    e.stopPropagation();
                    showBlackScreen();
                    setTimeout(hideBlackScreen, 500);
                    return false;
                }
            });

            // Block right-click
            $container.on('contextmenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // Block drag and drop
            $container.on('dragstart', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // Block selecting
            $container.on('selectstart', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // Show black screen when window loses focus
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    showBlackScreen();
                } else {
                    hideBlackScreen();
                }
            });

            $(window).on('blur', function() {
                showBlackScreen();
            });

            $(window).on('focus', function() {
                hideBlackScreen();
            });

            // Try to detect screenshot (experimental)
            let lastClipboardLength = -1;
            setInterval(() => {
                if (navigator.clipboard) {
                    navigator.clipboard.readText().then(text => {
                        if (text.length > lastClipboardLength && lastClipboardLength !== -1) {
                            // Possible copy action
                            showBlackScreen();
                            setTimeout(hideBlackScreen, 800);
                        }
                        lastClipboardLength = text.length;
                    }).catch(() => {});
                }
            }, 500);
        }

        function showBlackScreen() {
            $container.addClass('blur-content');
        }

        function hideBlackScreen() {
            $container.removeClass('blur-content');
        }

        if (hasPassword) {
            $submitPassword.on('click', function() {
                var password = $passwordInput.val();
                if (!password) return;

                $.ajax({
                    url: neoalbum_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'neoalbum_verify_password',
                        nonce: neoalbum_ajax.nonce,
                        album_id: albumId,
                        password: password
                    },
                    success: function(response) {
                        if (response.success) {
                            $container.find('.neoalbum-password-form').hide();
                            $bookWrapper.show();
                            initializeAlbum();
                        } else {
                            $passwordError.show();
                        }
                    }
                });
            });

            $passwordInput.on('keypress', function(e) {
                if (e.which === 13) {
                    $submitPassword.click();
                }
            });
        } else {
            initializeAlbum();
        }

        function initializeAlbum() {
            updatePages();
            bindEvents();
            applyTransform();
        }

        function updatePages() {
            var leftIndex = currentSpread * 2;
            var rightIndex = leftIndex + 1;

            if (images[leftIndex]) {
                $leftPage.find('.neoalbum-page-image').attr('src', images[leftIndex]);
                $leftPage.show();
            } else {
                $leftPage.hide();
            }

            if (images[rightIndex]) {
                $rightPage.find('.neoalbum-page-image').attr('src', images[rightIndex]);
                $rightPage.show();
            } else {
                $rightPage.hide();
            }

            $pageIndicator.text((currentSpread + 1) + ' / ' + totalSpreads);
            $prevBtn.prop('disabled', currentSpread === 0);
            $nextBtn.prop('disabled', currentSpread >= totalSpreads - 1);
        }

        function playPageFlipSound() {
            if (soundEnabled) {
                try {
                    var audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    var duration = 0.25;
                    var bufferSize = audioContext.sampleRate * duration;
                    var buffer = audioContext.createBuffer(1, bufferSize, audioContext.sampleRate);
                    var data = buffer.getChannelData(0);

                    for (var i = 0; i < bufferSize; i++) {
                        var envelope = Math.exp(-i / (bufferSize * 0.3));
                        data[i] = (Math.random() * 2 - 1) * 0.3 * envelope;
                    }

                    var noiseSource = audioContext.createBufferSource();
                    noiseSource.buffer = buffer;

                    var filter = audioContext.createBiquadFilter();
                    filter.type = 'lowpass';
                    filter.frequency.setValueAtTime(2000, audioContext.currentTime);
                    filter.frequency.exponentialRampToValueAtTime(200, audioContext.currentTime + duration);

                    var gain = audioContext.createGain();
                    gain.gain.setValueAtTime(0.4, audioContext.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration);

                    noiseSource.connect(filter);
                    filter.connect(gain);
                    gain.connect(audioContext.destination);

                    noiseSource.start();
                } catch (e) {}
            }
        }

        function flipNext() {
            if (isAnimating || currentSpread >= totalSpreads - 1) return;
            isAnimating = true;
            playPageFlipSound();

            $rightPage.css({
                'transform-origin': 'left center',
                'transform': 'rotateY(0deg)',
                'transition': 'none',
                'z-index': 10
            });

            $rightPage[0].offsetHeight;

            $rightPage.css({
                'transition': 'transform ' + (speed / 1000) + 's cubic-bezier(0.645, 0.045, 0.355, 1)',
                'transform': 'rotateY(-160deg)'
            });

            setTimeout(function() {
                currentSpread++;
                $rightPage.css({
                    'transform': 'rotateY(0deg)',
                    'transition': 'none',
                    'z-index': 1
                });
                updatePages();
                isAnimating = false;
            }, speed);
        }

        function flipPrev() {
            if (isAnimating || currentSpread === 0) return;
            isAnimating = true;
            playPageFlipSound();

            $leftPage.css({
                'transform-origin': 'right center',
                'transform': 'rotateY(0deg)',
                'transition': 'none',
                'z-index': 10
            });

            $leftPage[0].offsetHeight;

            $leftPage.css({
                'transition': 'transform ' + (speed / 1000) + 's cubic-bezier(0.645, 0.045, 0.355, 1)',
                'transform': 'rotateY(160deg)'
            });

            setTimeout(function() {
                currentSpread--;
                $leftPage.css({
                    'transform': 'rotateY(0deg)',
                    'transition': 'none',
                    'z-index': 1
                });
                updatePages();
                isAnimating = false;
            }, speed);
        }

        function toggleFullscreen() {
            isFullscreen = !isFullscreen;
            if (isFullscreen) {
                // Enter fullscreen
                $container.addClass('neoalbum-fullscreen');
                $fullscreenBtn.text('خروج');
                $zoomIcon.css('display', 'flex');

                // Create single page view
                fullscreenCurrentPage = currentSpread * 2;
                $fullscreenPage = $('<div class="neoalbum-fullscreen-single-page"></div>');
                $fullscreenImg = $('<img class="neoalbum-single-page-img" src="' + (images[fullscreenCurrentPage] || '') + '">');
                $fullscreenPage.append($fullscreenImg);
                $bookWrapper.prepend($fullscreenPage);

                // Reset fullscreen state
                fullscreenZoom = 1;
                fullscreenPanX = 0;
                fullscreenPanY = 0;
                applyFullscreenTransform();
                updateFullscreenIndicator();

                // Request fullscreen
                var elem = $container[0];
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen();
                }
            } else {
                // Exit fullscreen
                $container.removeClass('neoalbum-fullscreen');
                $fullscreenBtn.text('نمایش تمام صفحه');
                $zoomIcon.css('display', 'none');

                if ($fullscreenPage) {
                    $fullscreenPage.remove();
                    $fullscreenPage = null;
                    $fullscreenImg = null;
                }

                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }

                // Update normal view
                currentSpread = Math.floor(fullscreenCurrentPage / 2);
                updatePages();
                normalZoom = 1;
                normalPanX = 0;
                normalPanY = 0;
                applyTransform();
            }
        }

        function applyTransform() {
            if (isFullscreen) return;
            zoomLevel = normalZoom;
            panX = normalPanX;
            panY = normalPanY;

            $book.css({
                'transform': 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoomLevel + ')',
                'transform-origin': 'center center'
            });
        }

        // Zoom icon toggle for fullscreen
        function toggleFullscreenZoom() {
            if (!isFullscreen) return;
            if (fullscreenZoom > 1) {
                fullscreenZoom = 1;
                fullscreenPanX = 0;
                fullscreenPanY = 0;
            } else {
                fullscreenZoom = 3;
            }
            applyFullscreenTransform();
        }

        function applyFullscreenTransform() {
            if (!isFullscreen || !$fullscreenImg) return;

            if (fullscreenZoom === 1) {
                // At zoom level 1, no translation needed
                $fullscreenImg.css({
                    'transform': 'scale(1)',
                    'transform-origin': 'center center',
                    'transition': 'transform 0.1s ease-out'
                });
            } else {
                $fullscreenImg.css({
                    'transform': 'translate(' + fullscreenPanX + 'px, ' + fullscreenPanY + 'px) scale(' + fullscreenZoom + ')',
                    'transform-origin': 'center center',
                    'transition': 'transform 0.1s ease-out'
                });
            }
        }

        function updateFullscreenIndicator() {
            if (isFullscreen && $pageIndicator) {
                $pageIndicator.text((fullscreenCurrentPage + 1) + ' / ' + totalPages);
            }
        }

        function nextFullscreenPage() {
            if (fullscreenCurrentPage < totalPages - 1) {
                fullscreenCurrentPage++;
                if ($fullscreenImg) {
                    $fullscreenImg.attr('src', images[fullscreenCurrentPage] || '');
                }
                fullscreenZoom = 1;
                fullscreenPanX = 0;
                fullscreenPanY = 0;
                applyFullscreenTransform();
                updateFullscreenIndicator();
                playPageFlipSound();
            }
        }

        function prevFullscreenPage() {
            if (fullscreenCurrentPage > 0) {
                fullscreenCurrentPage--;
                if ($fullscreenImg) {
                    $fullscreenImg.attr('src', images[fullscreenCurrentPage] || '');
                }
                fullscreenZoom = 1;
                fullscreenPanX = 0;
                fullscreenPanY = 0;
                applyFullscreenTransform();
                updateFullscreenIndicator();
                playPageFlipSound();
            }
        }

        function getDistance(p1, p2) {
            var dx = p2.clientX - p1.clientX;
            var dy = p2.clientY - p1.clientY;
            return Math.sqrt(dx * dx + dy * dy);
        }

        function bindEvents() {
            // Normal view buttons
            $prevBtn.on('click', function() {
                if (isFullscreen) {
                    prevFullscreenPage();
                } else {
                    flipPrev();
                }
            });

            $nextBtn.on('click', function() {
                if (isFullscreen) {
                    nextFullscreenPage();
                } else {
                    flipNext();
                }
            });

            $fullscreenBtn.on('click', toggleFullscreen);
            $zoomIcon.on('click', toggleFullscreenZoom);

            // Mouse down for drag
            $bookWrapper.on('mousedown', startDrag);
            $(document).on('mousemove', drag);
            $(document).on('mouseup', endDrag);

            // Touch events
            $bookWrapper.on('touchstart', handleTouchStart);
            $(document).on('touchmove', handleTouchMove);
            $(document).on('touchend', handleTouchEnd);

            // Keyboard
            $(document).on('keydown', function(e) {
                if (!$bookWrapper.is(':visible')) return;
                
                if (e.keyCode === 37) {
                    if (isFullscreen) {
                        prevFullscreenPage();
                    } else {
                        flipPrev();
                    }
                } else if (e.keyCode === 39) {
                    if (isFullscreen) {
                        nextFullscreenPage();
                    } else {
                        flipNext();
                    }
                } else if (e.keyCode === 70) {
                    toggleFullscreen();
                } else if (e.keyCode === 27 && isFullscreen) {
                    toggleFullscreen();
                }
            });

            // Fullscreen change
            $(document).on('fullscreenchange webkitfullscreenchange msfullscreenchange', function() {
                if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
                    if (isFullscreen) {
                        isFullscreen = false;
                        $container.removeClass('neoalbum-fullscreen');
                        $fullscreenBtn.text('نمایش تمام صفحه');
                        $zoomIcon.css('display', 'none');
                        
                        if ($fullscreenPage) {
                            $fullscreenPage.remove();
                            $fullscreenPage = null;
                            $fullscreenImg = null;
                        }

                        currentSpread = Math.floor(fullscreenCurrentPage / 2);
                        updatePages();
                        normalZoom = 1;
                        normalPanX = 0;
                        normalPanY = 0;
                        applyTransform();
                    }
                }
            });
        }

        function startDrag(e) {
            if (isFullscreen) return; // Fullscreen drag handled in touch/mouse
            if (normalZoom <= 1) return;
            
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startPanX = normalPanX;
            startPanY = normalPanY;
            $book.css('cursor', 'grabbing');
        }

        function drag(e) {
            if (isFullscreen) {
                if (!isDragging) return;
                if (fullscreenZoom <= 1) return;

                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                fullscreenPanX = startPanX + dx;
                fullscreenPanY = startPanY + dy;
                applyFullscreenTransform();
            } else {
                if (!isDragging) return;
                if (normalZoom <= 1) return;

                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                normalPanX = startPanX + dx;
                normalPanY = startPanY + dy;
                applyTransform();
            }
        }

        function endDrag() {
            isDragging = false;
            $book.css('cursor', 'grab');
        }

        function handleTouchStart(e) {
            var touches = e.originalEvent.touches;
            
            if (touches.length === 1) {
                // Single touch - check for double tap or drag
                var currentTime = new Date().getTime();
                var tapLength = currentTime - lastTap;
                
                if (tapLength < 300 && tapLength > 0) {
                    // Double tap - toggle zoom
                    if (isFullscreen) {
                        if (fullscreenZoom > 1) {
                            fullscreenZoom = 1;
                            fullscreenPanX = 0;
                            fullscreenPanY = 0;
                        } else {
                            fullscreenZoom = 3;
                        }
                        applyFullscreenTransform();
                    } else {
                        if (normalZoom > 1) {
                            normalZoom = 1;
                            normalPanX = 0;
                            normalPanY = 0;
                        } else {
                            normalZoom = 2;
                        }
                        applyTransform();
                    }
                    lastTap = 0;
                    return;
                }
                
                lastTap = currentTime;
                
                // Start drag
                isDragging = true;
                startX = touches[0].clientX;
                startY = touches[0].clientY;
                
                if (isFullscreen) {
                    startPanX = fullscreenPanX;
                    startPanY = fullscreenPanY;
                } else {
                    startPanX = normalPanX;
                    startPanY = normalPanY;
                }
            } else if (touches.length === 2) {
                // Pinch to zoom
                isDragging = false;
                lastDist = getDistance(touches[0], touches[1]);
                lastZoom = isFullscreen ? fullscreenZoom : normalZoom;
            }
        }

        function handleTouchMove(e) {
            e.preventDefault();
            var touches = e.originalEvent.touches;
            
            if (touches.length === 1 && isDragging) {
                // Single touch drag
                if (isFullscreen) {
                    if (fullscreenZoom <= 1) return;
                    var dx = touches[0].clientX - startX;
                    var dy = touches[0].clientY - startY;
                    fullscreenPanX = startPanX + dx;
                    fullscreenPanY = startPanY + dy;
                    applyFullscreenTransform();
                } else {
                    if (normalZoom <= 1) return;
                    var dx = touches[0].clientX - startX;
                    var dy = touches[0].clientY - startY;
                    normalPanX = startPanX + dx;
                    normalPanY = startPanY + dy;
                    applyTransform();
                }
            } else if (touches.length === 2) {
                // Pinch to zoom
                var dist = getDistance(touches[0], touches[1]);
                var scale = dist / lastDist;
                
                if (isFullscreen) {
                    fullscreenZoom = lastZoom * scale;
                    fullscreenZoom = Math.min(maxZoom, Math.max(minZoom, fullscreenZoom));
                    applyFullscreenTransform();
                } else {
                    normalZoom = lastZoom * scale;
                    normalZoom = Math.min(maxZoom, Math.max(minZoom, normalZoom));
                    applyTransform();
                }
            }
        }

        function handleTouchEnd() {
            isDragging = false;
            lastDist = 0;
        }
    });
});
