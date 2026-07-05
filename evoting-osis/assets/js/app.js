document.addEventListener('DOMContentLoaded', () => {
    const detailModal = document.getElementById('candidateDetailModal');
    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const candidateId = button?.getAttribute('data-candidate-id') || '';
            const candidatePhoto = button?.getAttribute('data-candidate-photo') || '';
            const candidateNumber = button?.getAttribute('data-candidate-number') || '';
            const chairman = button?.getAttribute('data-candidate-chairman') || '';
            const vice = button?.getAttribute('data-candidate-vice') || '';
            const vision = button?.getAttribute('data-candidate-vision') || '-';
            const mission = button?.getAttribute('data-candidate-mission') || '-';
            const candidateName = `Nomor ${candidateNumber} - ${chairman} & ${vice}`;

            const photo = document.getElementById('detailCandidatePhoto');
            const number = document.getElementById('detailCandidateNumber');
            const chairmanLabel = document.getElementById('detailCandidateChairman');
            const viceLabel = document.getElementById('detailCandidateVice');
            const visionLabel = document.getElementById('detailCandidateVision');
            const missionList = document.getElementById('detailCandidateMission');
            const voteButton = document.getElementById('detailVoteButton');

            if (photo && candidatePhoto) {
                photo.src = candidatePhoto;
                photo.alt = `Foto pasangan calon nomor ${candidateNumber}`;
            }
            if (number) number.textContent = candidateNumber;
            if (chairmanLabel) chairmanLabel.textContent = chairman;
            if (viceLabel) viceLabel.textContent = `Wakil Ketua: ${vice}`;
            if (visionLabel) visionLabel.textContent = vision;

            if (missionList) {
                const items = mission
                    .split(/\r?\n|;|(?=\s*\d+[.)]\s+)/)
                    .map((item) => item.replace(/^\s*(?:[-*]|\d+[.)])\s*/, '').trim())
                    .filter(Boolean);

                missionList.innerHTML = '';
                (items.length ? items : [mission]).forEach((item) => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    missionList.appendChild(li);
                });
            }

            if (voteButton) {
                voteButton.setAttribute('data-candidate-id', candidateId);
                voteButton.setAttribute('data-candidate-name', candidateName);
            }
        });
    }

    const confirmModal = document.getElementById('confirmVoteModal');
    if (confirmModal) {
        confirmModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const form = document.getElementById('voteForm');
            const candidateId = button?.getAttribute('data-candidate-id');
            const candidateName = button?.getAttribute('data-candidate-name');
            const label = document.getElementById('confirmCandidateName');

            if (form && candidateId) {
                form.querySelector('[name="candidate_id"]').value = candidateId;
            }

            if (label && candidateName) {
                label.textContent = candidateName;
            }
        });
    }

    const actionConfirmModal = document.getElementById('actionConfirmModal');
    if (actionConfirmModal) {
        let pendingForm = null;
        const confirmTitle = document.getElementById('actionConfirmTitle');
        const confirmText = document.getElementById('actionConfirmText');
        const continueButton = document.getElementById('actionConfirmContinue');
        const phraseWrap = document.getElementById('confirmPhraseWrap');
        const phraseInput = document.getElementById('confirmPhraseInput');
        const phraseLabel = document.getElementById('confirmPhraseLabel');

        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                if (confirmTitle) confirmTitle.textContent = form.dataset.confirmTitle || 'Apakah Anda yakin?';
                if (confirmText) confirmText.textContent = form.dataset.confirm || 'Tindakan ini tidak dapat dibatalkan.';
                const phrase = form.dataset.confirmPhrase || '';
                if (phraseWrap && phraseInput && phraseLabel && continueButton) {
                    phraseInput.value = '';
                    phraseInput.dataset.phrase = phrase;
                    phraseLabel.textContent = phrase;
                    phraseWrap.classList.toggle('d-none', phrase === '');
                    continueButton.disabled = phrase !== '';
                }
                bootstrap.Modal.getOrCreateInstance(actionConfirmModal).show();
            });
        });

        if (phraseInput && continueButton) {
            phraseInput.addEventListener('input', () => {
                const phrase = phraseInput.dataset.phrase || '';
                continueButton.disabled = phrase !== '' && phraseInput.value.trim() !== phrase;
            });
        }

        if (continueButton) {
            continueButton.addEventListener('click', () => {
                if (!pendingForm) return;
                const phrase = phraseInput?.dataset.phrase || '';
                if (phrase) {
                    let hiddenPhrase = pendingForm.querySelector('[name="reset_phrase"]');
                    if (!hiddenPhrase) {
                        hiddenPhrase = document.createElement('input');
                        hiddenPhrase.type = 'hidden';
                        hiddenPhrase.name = 'reset_phrase';
                        pendingForm.appendChild(hiddenPhrase);
                    }
                    hiddenPhrase.value = phraseInput.value.trim();
                }
                pendingForm.dataset.confirmed = 'true';
                pendingForm.submit();
            });
        }
    }

    const countdown = document.querySelector('[data-countdown]');
    if (countdown) {
        let remaining = Number(countdown.dataset.countdown || 5);
        countdown.textContent = remaining.toString();

        const timer = setInterval(() => {
            remaining -= 1;
            countdown.textContent = Math.max(remaining, 0).toString();

            if (remaining <= 0) {
                clearInterval(timer);
                window.location.href = countdown.dataset.redirect || 'login.php';
            }
        }, 1000);
    }

    const chartCanvas = document.getElementById('resultsChart');
    const pieCanvas = document.getElementById('resultsPieChart');
    const palette = ['#1E40AF', '#2563EB', '#D4AF37', '#22C55E', '#F59E0B', '#DC2626'];

    if ((chartCanvas || pieCanvas) && window.Chart) {
        const endpoint = chartCanvas?.dataset.endpoint || pieCanvas?.dataset.endpoint;
        let barChart = null;
        let pieChart = null;

        if (chartCanvas) {
            barChart = new Chart(chartCanvas, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Perolehan Suara',
                        data: [],
                        backgroundColor: '#1E40AF',
                        borderRadius: 14,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const meta = context.rawMeta || context.dataset.meta?.[context.dataIndex];
                                    return meta ? `${meta.votes} suara (${meta.percentage})` : `${context.parsed.y} suara`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E2E8F0' }, border: { display: false } },
                        x: { grid: { display: false }, ticks: { color: '#475569', maxRotation: 0, autoSkip: false } },
                    },
                },
            });
        }

        if (pieCanvas) {
            pieChart = new Chart(pieCanvas, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: palette,
                        borderColor: '#ffffff',
                        borderWidth: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { position: 'bottom' } },
                },
            });
        }

        const loadResults = async () => {
            try {
                const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                if (barChart) {
                    barChart.data.labels = payload.labels;
                    barChart.data.datasets[0].data = payload.values;
                    barChart.data.datasets[0].meta = payload.meta || [];
                    barChart.update();
                }

                if (pieChart) {
                    pieChart.data.labels = payload.labels;
                    pieChart.data.datasets[0].data = payload.values;
                    pieChart.update();
                }
            } catch (error) {
                console.warn('Gagal memuat grafik hasil voting.', error);
            }
        };

        loadResults();
        setInterval(loadResults, 10000);
    }
});
