<?php
    $question = $args['question'];
    $rcq_response_info = rcq_get_responses_from_meta( $question );
?>
<div data-rcq-question-object class="rcq-question">
    <p class="rcq-question-title"><?php echo $question->post_title; ?></p>
    <ul>
        <?php foreach ( $rcq_response_info['choices'] as $key => $rcq_choice ): ?>
            <li data-rcq-question-option data-is-correct="<?php echo ($key == $rcq_response_info['correct_index']) ? 'true' : 'false'; ?>" ><?php echo $rcq_choice ?></li>
        <?php endforeach; ?>
    </ul>
</div>