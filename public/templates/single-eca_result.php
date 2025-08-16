<?php
/**
 * The template for displaying a single ECA Result.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Educational_Consulting_App
 * @subpackage Educational_Consulting_App/public/templates
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        while ( have_posts() ) :
            the_post();

            $result_id = get_the_ID();
            $final_code = get_post_meta( $result_id, '_holland_code', true );
            $scores = get_post_meta( $result_id, '_holland_scores', true );

            // Descriptions for each Holland type (can be expanded)
            $descriptions = array(
                'R' => '<strong>Realistic (Doers):</strong> People who like to work with their hands, tools, and machines. They are often practical and mechanical.',
                'I' => '<strong>Investigative (Thinkers):</strong> People who like to observe, learn, analyze, and solve problems. They are often scientific and intellectual.',
                'A' => '<strong>Artistic (Creators):</strong> People who have artistic, innovating, or intuitional abilities and like to work in unstructured situations using their imagination and creativity.',
                'S' => '<strong>Social (Helpers):</strong> People who like to work with people to enlighten, inform, help, train, or cure them. They are often skilled with words.',
                'E' => '<strong>Enterprising (Persuaders):</strong> People who like to work with people, influencing, persuading, performing, leading or managing for organizational goals or economic gain.',
                'C' => '<strong>Conventional (Organizers):</strong> People who like to work with data, have clerical or numerical ability, carry things out in detail or follow through on others\' instructions.',
            );
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <div class="entry-content">

                    <div class="eca-pdf-button-container">
                        <button onclick="window.print();" class="eca-pdf-button"><?php _e( 'Download as PDF', 'educational-consulting-app' ); ?></button>
                    </div>

                    <h1><?php echo get_the_title(get_post_meta($result_id, '_assessment_id', true)); ?></h1>
                    <h2>Results for <?php echo get_the_author_meta('display_name', get_post_field('post_author', $result_id)); ?></h2>
                    <hr>

                    <h3>Your Holland Code is: <?php echo esc_html( $final_code ); ?></h3>

                    <p>This code represents your top three areas of interest based on the assessment you just took. Read more about them below.</p>

                    <h3>Your Interest Profile</h3>
                    <ul>
                        <?php
                        if(is_array($scores)){
                            foreach($scores as $code => $score){
                                echo '<li><strong>' . esc_html($code) . ':</strong> ' . esc_html($score) . ' points</li>';
                            }
                        }
                        ?>
                    </ul>

                    <h3>Understanding Your Code</h3>
                    <?php
                    $top_codes = str_split($final_code);
                    foreach($top_codes as $code){
                        if(isset($descriptions[$code])){
                            echo '<p>' . $descriptions[$code] . '</p>';
                        }
                    }
                    ?>
                </div>

            </article>

        <?php endwhile; // End of the loop. ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
