<?php
/*
Plugin Name: Quiz Plugin
Description: Dynamic multi-step quiz with admin-managed questions.
Version: 1.0
Author: Mirai Infotech
*/

if (!defined('ABSPATH')) exit; // Security

/*********** Admin Menu ***********/

add_action('admin_menu', 'quiz_plugin_admin_menu');

function quiz_plugin_admin_menu()
{
    add_menu_page('Quiz Plugin', 'Quiz Plugin', 'manage_options', 'quiz-plugin', 'quiz_plugin_admin_page', 'dashicons-welcome-learn-more', 25);
}
/*********** Enqueue Scripts ***********/
// add_action('wp_enqueue_scripts', 'quiz_plugin_enqueue_scripts');
// function quiz_plugin_enqueue_scripts()
// {
//     wp_enqueue_script('jquery');
// }

add_action('wp_enqueue_scripts', 'quiz_plugin_enqueue_scripts');

function quiz_plugin_enqueue_scripts()
{
    wp_enqueue_style(
        'quiz-plugin-css',
        plugin_dir_url(__FILE__) . 'css/quiz-plugin.css',
        [],
        '1.0'
    );

    // wp_enqueue_script(
    //     'quiz-plugin-js',
    //     plugin_dir_url(__FILE__) . 'js/quiz-plugin.js',
    //     ['jquery'],
    //     '1.0',
    //     true
    // );
}


/*********** Register Settings ***********/

add_action('admin_init', 'quiz_plugin_register_settings');

function quiz_plugin_register_settings()
{
    register_setting(
        'quiz_plugin_options',
        'quiz_plugin_questions',
        'quiz_plugin_sanitize_questions'
    );
    // register_setting('quiz_plugin_options', 'quiz_plugin_results_settings');
}

/*********** Sanitize Callback ***********/

function quiz_plugin_sanitize_questions($input)
{
    if (!is_array($input)) return [];

    $output = [];

    foreach ($input as $q) {
        if (empty($q['text']) || empty($q['type'])) continue;

        $text = sanitize_text_field($q['text']);
        $type = sanitize_text_field($q['type']);

        $question = [
            'text' => $text,
            'type' => $type,
        ];

        // Only MCQ needs options
        if ($type === 'choice' && !empty($q['options']) && is_array($q['options'])) {
            $options = [];
            foreach ($q['options'] as $opt) {
                $opt = trim($opt);
                if ($opt !== '') {
                    $options[] = sanitize_text_field($opt);
                }
            }

            if (empty($options)) continue;
            $question['options'] = $options;
        }

        $output[] = $question;
    }

    return $output;
}

/*********** Admin Page ***********/
function quiz_plugin_admin_page()
{
?>
    <div class="wrap">
        <h1>Multiple Choice Quiz Questions</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('quiz_plugin_options');
            $questions = get_option('quiz_plugin_questions', []);
            ?>

            <div id="questions-wrapper">
                <div id="questions-container">
                    <?php
                    if (!empty($questions)) {
                        foreach ($questions as $index => $q) {
                    ?>
                            <div class="question-block" data-index="<?php echo $index; ?>" style="margin-bottom:15px; border-bottom:1px solid #ccc; padding:10px;">

                                <h3 class="question-label">Question</h3>
                                <input type="text" name="quiz_plugin_questions[<?php echo $index; ?>][text]" value="<?php echo esc_attr($q['text']); ?>" placeholder="Question Text" style="width:50%;" required>


                                <select name="quiz_plugin_questions[<?php echo $index; ?>][type]" class="question-type">
                                    <option value="choice" <?php selected($q['type'] ?? '', 'choice'); ?>>Multiple Choice</option>
                                    <option value="input" <?php selected($q['type'] ?? '', 'input'); ?>>Input</option>
                                </select>


                                <!-- <div class="options-container" style="margin-top:5px;"> -->
                                <div class="options-container" style="<?php echo (($q['type'] ?? 'choice') === 'input') ? 'display:none;' : ''; ?>">
                                    <?php foreach ($q['options'] as $opt_index => $opt) { ?>
                                        <input type="text" name="quiz_plugin_questions[<?php echo $index; ?>][options][]" value="<?php echo esc_attr($opt); ?>" placeholder="Option" style="width:100%; max-width:600px; display:block; margin-bottom:8px;" required>
                                    <?php } ?>
                                    <button class="button add-option">Add Option</button>
                                </div>
                                <button class="button remove-question" style="margin-top:5px;">Remove Question</button>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
                <button class="button button-primary" id="add-question">Add Question</button>
            </div>

            <hr>

            <?php
            // ✅ LOAD SETTINGS SAFELY
            $results_settings = get_option('quiz_plugin_results_settings', [
                'systems_leak'  => 'Your current bottleneck is a Systems Leak. You are losing efficiency in your conversion funnel.',
                'authority_gap' => 'Your current bottleneck is an Authority Gap. AI engines don’t know you exist.',
                'cta_link'      => ''
            ]);
            ?>

            

            <?php submit_button(); ?>

        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {

            function updateLabels() {
                $('#questions-container .question-block').each(function(i) {
                    $(this).attr('data-index', i);
                    $(this).find('.question-label').text('Question ' + (i + 1));

                    $(this).find('input, textarea, select').each(function() {
                        let name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/\[\d*\]/, '[' + i + ']'));
                        }
                    });
                });
            }

            let count = $('#questions-container > div').length;

            // Add new question
            $('#add-question').click(function(e) {
                e.preventDefault();

                let html = `
                            <div class="question-block" data-index="0" style="margin-bottom:15px;border-bottom:1px solid #ccc;padding:10px;">
                                <h3 class="question-label">Question</h3>

                                <input type="text" name="quiz_plugin_questions[][text]"
                                    placeholder="Question Text"
                                    style="width:100%;max-width:600px;margin-bottom:8px;" required>

                                <select name="quiz_plugin_questions[][type]" class="question-type">
                                    <option value="choice">Multiple Choice</option>
                                    <option value="input">Input</option>
                                </select>

                                <div class="options-container" style="margin-top:8px;">
                                    <input type="text" name="quiz_plugin_questions[][options][]"
                                        placeholder="Option" style="width:40%;" required>
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


            $(document).on('change', '.question-type', function() {
                let block = $(this).closest('.question-block');
                let optionsBox = block.find('.options-container');
                let optionInputs = optionsBox.find('input[type="text"]');

                if ($(this).val() === 'input') {
                    optionsBox.hide();
                    optionInputs.prop('required', false).prop('disabled', true);
                } else {
                    optionsBox.show();
                    optionInputs.prop('disabled', false).prop('required', true);
                }
            });





        });
    </script>
<?php
}

/*********** Frontend Shortcode ***********/
add_shortcode('quiz_plugin', 'quiz_plugin_render_form');
function quiz_plugin_render_form()
{
    $questions = get_option('quiz_plugin_questions', []);
    // $results_settings = get_option('quiz_plugin_results_settings', []);
    if (empty($questions)) return "<p>No questions found. Please add questions in admin panel.</p>";

    ob_start();
?>
    <div class="quiz-container">
        <form id="quizForm">
            <!-- Intro Step -->
            <div class="step intro-step" data-step="intro">
                <div class="site-flex-v gap-32 justify-center">
                    <img class="quiz-blub-img" alt="quiz growth leaks" src="https://thebrandoodle.com/wp-content/uploads/2026/01/brandoodle-monogram-sm.png">
                    <div class="site-flex-v">
                        <h3 class="text-center">Ready to find your growth leaks?</h3>
                        <p class="text-center">Answer 3 quick questions to uncover what's holding back your marketing.</p>
                    </div>


                    <button type="button" id="startQuizBtn">
                        Start the Quiz →
                    </button>
                </div>

            </div>



            <?php foreach ($questions as $i => $q): ?>
                <?php $options = isset($q['options']) && is_array($q['options']) ? $q['options'] : []; ?>

                <div class="step" data-step="<?php echo $i; ?>">

                    <div class="quiz-step-inner">

                        <div class="quiz-progress-wrap">
                            <div class="quiz-progress-top">
                                <span>
                                    Question <span class="currentStep">1</span>
                                    of <span class="totalSteps"><?php echo count($questions); ?></span>
                                </span>
                                <span>
                                    <span class="progressPercent">0</span>% complete
                                </span>
                            </div>

                            <div class="quiz-progress-bar">
                                <div class="quiz-progress-fill"></div>
                            </div>
                        </div>





                        <h3><?php echo esc_html($q['text']); ?></h3>

                        <div class="quiz-step-options-wrap">
                            <?php
                            // Q4 = input field
                            if ($q['type'] === 'input'): ?>
                                <input type="text"
                                    name="q<?php echo $i; ?>"
                                    placeholder="Enter answer"
                                    required>
                            <?php else: ?>
                                <?php foreach ($q['options'] as $opt): ?>
                                    <label>
                                        <input type="radio"
                                            name="q<?php echo $i; ?>"
                                            value="<?php echo esc_attr($opt); ?>"
                                            required>
                                        <?php echo esc_html($opt); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>

            <div class="quiz-step-nav-btn-wrap">
                <button type="button" id="prevBtn" style="display:none;">Back</button>
                <button type="button" id="nextBtn">Next</button>
            </div>

            <div class="step result-step">
                <div class="quiz-result-card">
                    <div>
                        <h3 class="quiz-result-title text-center">Your Growth Diagnosis</h3>
                        <p class="quiz-result-subtitle text-center">
                            Based on your answers, here's what we found:
                        </p>
                    </div>
                    

                    <div id="quizResult">
                    </div>

                    <div class="quiz-result-actions text-center">
                        <a class="fill-pop-up-btn" href="#" target="_blank">Get My Fix</a>
                        <button type="button" id="retakeQuizBtn" class="retake-btn">
                            Retake Quiz
                        </button>
                    </div>

                </div>
            </div>

        </form>


    </div>

    <script>
        jQuery(document).ready(function($) {

        let steps = $('.step').not('.result-step').not('.intro-step');
        let introStep = $('.intro-step');
        let resultStep = $('.result-step');
        let current = 0;

        function showStep(n) {
            $('.step').hide();
            resultStep.hide();

            steps.eq(n).show();

            updateProgress();

            // $('#prevBtn').toggle(n > 0);

            // $('#prevBtn').show().prop('disabled', n === 0);

             $('#prevBtn').show();

            $('#nextBtn')
                .text(n === steps.length - 1 ? 'See Results' : 'Next')
                .prop('disabled', true)
                .show();

            checkNextButton();
        }

        const totalSteps = steps.length;

        function updateProgress() {
            const stepNumber = current + 1;
            const percent = Math.round((stepNumber / totalSteps) * 100);

            let step = steps.eq(current);
            step.find('.currentStep').text(stepNumber);
            step.find('.progressPercent').text(percent);
            step.find('.quiz-progress-fill').css('width', percent + '%');
        }

    function checkNextButton() {
        let radios = $('input[name="q' + current + '"]');
        let textInput = $('input[name="q' + current + '"][type="text"]');

        let isValid = false;

        if (radios.length) {
            isValid = radios.is(':checked');
        }

        if (textInput.length) {
            isValid = $.trim(textInput.val()) !== '';
        }

        $('#nextBtn').prop('disabled', !isValid);
    }

    // Init
    $('.step').hide();
    introStep.show();
    $('#prevBtn, #nextBtn').hide();

    $('#startQuizBtn').click(function() {
        introStep.hide();
        current = 0;
        showStep(current);
    });

    // RADIO OPTION ACTIVE CLASS
    $(document).on('change', 'input[type="radio"]', function() {
        let name = $(this).attr('name');

        $('input[name="' + name + '"]')
            .closest('label')
            .removeClass('active');

        $(this).closest('label').addClass('active');

        checkNextButton();
    });

    // TEXT INPUT CHECK
    $(document).on('input', 'input[type="text"]', function() {
        checkNextButton();
    });

    $('#nextBtn').click(function() {
        if ($(this).prop('disabled')) return;

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

        let html = '';

        // Q2 = YES → Systems Leak
        if (q2 === 'Yes') {
            html += `
                <div class="quiz-final-result quiz-warning">
                    <div class="quiz-warning-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-triangle-alert w-6 h-6 text-primary"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg></div>
                    <div class="quiz-warning-content">
                        <h4>You have a Systems Leak</h4>
                        <p>Your current bottleneck is a <strong>Systems Leak</strong>. You are losing efficiency in your conversion funnel. </p>
                    </div>
                </div>
            `;
        }

        // Q3 = NO → Authority Gap
        if (q3 === 'No') {
            html += `
                <div class="quiz-final-result quiz-warning">
                    <div class="quiz-warning-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb w-6 h-6 text-primary"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"></path><path d="M9 18h6"></path><path d="M10 22h4"></path></svg></div>
                    <div class="quiz-warning-content">
                        <h4>You have an Authority Gap</h4>
                        <p>Your current bottleneck is an <strong>Authority Gap</strong>. AI engines don't know you exist.</p>
                    </div>
                </div>
            `;
        }

        // ✅ SUCCESS STATE
        if (!html) {
            html = `
                <div class="quiz-final-result quiz-success">
                    <div class="quiz-success-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-6 h-6 text-green-600"><path d="M21.801 10A10 10 0 1 1 17 3.335"></path><path d="m9 11 3 3L22 4"></path></svg></div>
                    <div class="quiz-success-content">
                        <h4>Your growth foundation looks solid!</h4>
                        <p>But there's always room to optimize. Let's discuss how to take your marketing from good to exceptional.</p>
                    </div>
                </div>
            `;
        }

        $('#quizResult').html(html);
    }



    $('#retakeQuizBtn').click(function() {

    // Reset state
    current = 0;
    window.hasMovedForward = false;

    // Clear answers
    $('#quizForm')[0].reset();

    // Remove active classes
    $('.quiz-step-options-wrap label').removeClass('active');

    // Hide result
    resultStep.hide();
    $('#quizResult').html('');

    // Hide navigation
    $('#prevBtn, #nextBtn').hide();

    // Show intro step
    introStep.show();

$('.quiz-progress-fill').css('width', '0%');
$('#currentStep').text(1);
$('#progressPercent').text(0);

});


});

    </script>
    <style>
/* .quiz-step-options-wrap label {
    display: block;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    margin-bottom: 10px;
    transition: all 0.2s ease;
}

.quiz-step-options-wrap label.active {
    background: #F7BF3A1A;
    border-color: #F1C232;
}

#nextBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quiz-progress-wrap {
    margin-bottom: 24px;
}

.quiz-progress-top {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #475569;
    margin-bottom: 8px;
}

.quiz-progress-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 999px;
    overflow: hidden;
}

.quiz-progress-fill {
    height: 100%;
    width: 0%;
    background: #facc15;
    border-radius: 999px;
    transition: width 0.3s ease;
}

#prevBtn:disabled {
    opacity: 0.5;          
    cursor: not-allowed;   
    pointer-events: none; 
    transition: all 0.2s ease;
}

 */

        </style>
<?php
    return ob_get_clean();
}
