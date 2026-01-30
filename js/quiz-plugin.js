console.log("working");

/*************************** Admin settings js ****************************************/ 

jQuery(document).ready(function($) {

            function updateLabels() {
                $('#questions-container .question-block').each(function(i) {
                    $(this).attr('data-index', i);
                    $(this).find('.question-label').text('Question ' + (i + 1));
                    $(this).find('input, textarea').each(function() {
                        let name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                        }
                    });
                });
            }



            let count = $('#questions-container > div').length;

            // Add new question
            $('#add-question').click(function(e) {
                e.preventDefault();

                let html = `
                <div class="question-block" data-index="0" style="margin-bottom:15px; border-bottom:1px solid #ccc; padding:10px;">

                    <h3 class="question-label">Question</h3>

                    <input type="text" name="quiz_plugin_questions[][text]" placeholder="Question Text" style="width:100%; max-width:600px; display:block; margin-bottom:8px;" required>

                    <div class="options-container" style="margin-top:5px;">
                        <input type="text" name="quiz_plugin_questions[][options][]" placeholder="Option" style="width:40%;" required>
                        <button class="button add-option">Add Option</button>
                    </div>
                    <button class="button remove-question" style="margin-top:5px;">Remove Question</button>
                </div>`;

                $('#questions-container').append(html);

                // EXISTING: this now works correctly
                updateLabels();
            });

            // Remove question
            $(document).on('click', '.remove-question', function(e) {
                e.preventDefault();
                $(this).parent().remove();
                updateLabels();
            });

            // Add option inside question
            $(document).on('click', '.add-option', function(e) {
                e.preventDefault();
                let container = $(this).parent();
                let index = container.parent().data('index');
                container.append('<input type="text" name="quiz_plugin_questions[' + index + '][options][]" placeholder="Option" style="width:40%; margin-top:3px;" required>');
                updateLabels();
            });
            updateLabels();
        });


/*********** Frontend  js ***********/


jQuery(document).ready(function($) {

          //  let steps = $('.step').not('.result-step');
          let steps = $('.step').not('.result-step').not('.intro-step');
          let introStep = $('.intro-step');

            let resultStep = $('.result-step');
            let current = 0;

            // function showStep(n) {
            //     steps.hide();
            //     resultStep.hide();

            //     steps.eq(n).show();

            //     $('#prevBtn').toggle(n > 0);

            //     $('#nextBtn').text(
            //         n === steps.length - 1 ? 'See Results' : 'Next'
            //     ).show();
            // }

            function showStep(n) {
                $('.step').hide();
                resultStep.hide();

                steps.eq(n).show();

                $('#prevBtn').toggle(n > 0);
                $('#nextBtn').text(
                    n === steps.length - 1 ? 'See Results' : 'Next'
                ).show();
            }


            // showStep(current);

            $('.step').hide();
                introStep.show();
                $('#prevBtn, #nextBtn').hide();


                $('#startQuizBtn').click(function () {
                    introStep.hide();
                    current = 0;
                    showStep(current);
                    $('#nextBtn').show();
            });


            $('#nextBtn').click(function() {

                let inputRadio = $('input[name="q' + current + '"]:checked');
                let inputText = $('input[name="q' + current + '"]');

                if (inputRadio.length === 0 && inputText.length && !inputText.val()) {
                    alert('Please fill the field');
                    return;
                }

                if (inputRadio.length === 0 && inputText.length === 0) {
                    alert('Please select an option');
                    return;
                }

                if (current === steps.length - 1) {
                    showResults();
                    steps.hide();
                    resultStep.show();
                    $('#prevBtn, #nextBtn').hide();
                    return;
                }

                current++;
                showStep(current);
            });

            $('#prevBtn').click(function() {
                if (current > 0) {
                    current--;
                    showStep(current);
                }
            });

            function showResults() {

                let q2 = $('input[name="q1"]:checked').val();
                let q3 = $('input[name="q2"]:checked').val();

                let resultBox = $('#quizResult');

                let systems = resultBox.data('systems');
                let authority = resultBox.data('authority');
                let cta = resultBox.data('cta');

                let messages = [];

                if (q2 === 'Yes' && systems) {
                    messages.push(systems);
                }

                if (q3 === 'No' && authority) {
                    messages.push(authority);
                }

                if (messages.length === 0) {
                    messages.push('Thank you for completing the quiz.');
                }

                let html = messages.map(msg =>
                    `<p><strong>${msg}</strong></p>`
                ).join('');

                if (cta) {
                    html += `<a href="${cta}" target="_blank"
                            style="display:inline-block;margin-top:15px;
                            padding:10px 16px;background:#000;color:#fff;
                            text-decoration:none;">
                            Get My Fix
                            </a>`;
                }
                resultBox.html(html);
            }
        });