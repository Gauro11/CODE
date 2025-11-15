<?php
/**
 * Enhanced PHP Sentiment Analyzer with Detailed VADER Process Visualization
 * Shows step-by-step analysis like the reference image
 */
class SentimentAnalyzer {
    
   private $positive_words = [
    'excellent','amazing','wonderful','fantastic','great','good','best','perfect','love','awesome','outstanding',
    'brilliant','superb','nice','happy','pleased','satisfied','impressed','recommended','professional','clean',
    'efficient','friendly','helpful','polite','thorough','quick','reliable','trustworthy','exceptional','impressive',
    'quality','beautiful','spotless','meticulous','careful','terrific','delighted','fabulous','skilled','competent',
    'courteous','prompt','attentive','thankful','grateful','appreciated','worth','recommend','flawless','pristine',
    'sparkling','immaculate','gleaming','shining','fresh','organized','tidy','highly','recommended','excellent job',
    'fantastic job','went above and beyond','above and beyond','great experience','extremely happy','spotless',
    'everything looks spotless','smells so fresh','very professional','friendly and efficient',

    
    'awesome service','high quality','super clean','very satisfied','perfect job','top notch','super fast',
    'very friendly','great work','amazing service','excellent service','very helpful','great attitude',
    'outstanding job','great communication','very neat','super neat','well done','perfectly done',
    'beyond expectations','more than expected','five star','5 star','fast service','on time','very organized',
    'love the result','top quality','super helpful','very polite','great cleaner','amazing cleaner',
    'excellent cleaning','best experience','very trustworthy','super professional','highly impressed',
    'very thorough','very detailed','excellent attention to detail','beautiful work','very smooth process',
    'very convenient','great teamwork','nice job','very clean','extremely clean','everything is perfect'
];

    
   private $negative_words = [
    'bad','terrible','awful','horrible','poor','worst','disappointing','disappointed','unhappy','unsatisfied',
    'rude','unprofessional','late','delayed','dirty','messy','careless','sloppy','incomplete','missing','broken',
    'damaged','waste','never','not','no',"don't","won't","can't","shouldn't","wouldn't",'inadequate','subpar',
    'unacceptable','frustrating','annoying','disgusting','mediocre','useless','pathetic','inferior','defective',
    'faulty','incompetent','lazy','negligent','disrespectful','unreliable','unsatisfactory','dusty','grimy',
    'stained','smelly','filthy','overlooked','rushed','skipped','untouched','missed spots','missed several spots',
    'bathroom still dirty','completely unsatisfied','not worth','poor quality','very slow','careless work',
    'dirty after they left','didn’t clean properly','didnt clean properly','unprofessional and rude',

    'horrible service','very bad','really bad','super slow','poor service','not satisfied','not happy at all',
    'not good','terrible experience','bad experience','did not like','not clean','still dirty','still messy',
    'not worth the money','waste of money','waste of time','very rude','very unprofessional','not helpful',
    'didn’t finish the job','did not finish the job','unfinished work','left early','did not show up',
    'no show','never came','came late','very late','very disappointing','not cleaned properly',
    'poor attitude','dirty work','unacceptable service','very careless','not organized','very messy',
    'terrible cleaning','horrible cleaning','still dust everywhere','missed a lot of areas','missed many spots',
    'did a poor job','not detailed','rushed job','very noisy','bad smell left','did not follow instructions',
    'poor communication','didn’t arrive on time','unreliable service','low quality','terrible quality'
];

private $neutral_phrases = [
    'normal service',
    'just a regular cleaning',
    'it was alright',
    'not impressive but not terrible',
    'nothing special',
    'okay service',
    'average experience',
    'nothing stood out',
    'it was fine'
];

    
    private $intensifiers = [
        'very' => 1.5,
        'extremely' => 2.0,
        'absolutely' => 1.8,
        'completely' => 1.7,
        'totally' => 1.6,
        'really' => 1.3,
        'quite' => 1.2,
        'incredibly' => 1.9,
        'highly' => 1.4,
        'so' => 1.3,
        'super' => 1.5
    ];
    
    // Store detailed analysis steps
    private $analysis_steps = [];
    
    /**
     * Main analyze function with detailed step tracking
     */
    public function analyze($text) {
        $this->analysis_steps = []; // Reset steps
        $scores = $this->polarity_scores($text);
        
        if ($scores['compound'] >= 0.05) {
            return 'Positive';
        } elseif ($scores['compound'] <= -0.05) {
            return 'Negative';
        } else {
            return 'Neutral';
        }
    }
    
    /**
     * Get detailed polarity scores with step-by-step tracking
     */
    public function polarity_scores($text) {
        $original_text = $text;
        $text = strtolower($text);
        
        // Step 1: Text Cleaning
        $this->analysis_steps['step1'] = [
            'title' => '1. Text Cleaning',
            'description' => 'All feedback is converted to lowercase and unnecessary symbols or punctuation are removed to make the text uniform.',
            'original' => $original_text,
            'cleaned' => $text
        ];
        
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($words)) {
            return ['compound' => 0, 'pos' => 0, 'neg' => 0, 'neu' => 1];
        }
        
        $positive_score = 0;
        $negative_score = 0;
        $neutral_count = 0;
        $word_analysis = [];
        
        // Step 2: Word Scoring
        for ($i = 0; $i < count($words); $i++) {
            $word = $this->clean_word($words[$i]);
            
            if (empty($word)) {
                continue;
            }
            
            $intensifier = 1.0;
            $intensifier_word = null;
            
            if ($i > 0) {
                $prev_word = $this->clean_word($words[$i - 1]);
                if (isset($this->intensifiers[$prev_word])) {
                    $intensifier = $this->intensifiers[$prev_word];
                    $intensifier_word = $prev_word;
                }
            }
            
            // Step 3: Context Adjustment (Negation Detection)
            $negation = false;
            $negation_word = null;
            if ($i > 0) {
                $prev_word = $this->clean_word($words[$i - 1]);
                if (in_array($prev_word, ['not', 'no', 'never', 'dont', 'didnt', 'wont', 'wouldnt', 'cant', 'couldnt', 'isnt', 'arent', 'wasnt', 'werent'])) {
                    $negation = true;
                    $negation_word = $prev_word;
                }
            }
            
            // Score the word
            $word_score = 0;
            $word_type = 'neutral';
            
            if (in_array($word, $this->positive_words)) {
                if ($negation) {
                    $word_score = -(1.0 * $intensifier);
                    $negative_score += (1.0 * $intensifier);
                    $word_type = 'negative (negated positive)';
                } else {
                    $word_score = (1.0 * $intensifier);
                    $positive_score += (1.0 * $intensifier);
                    $word_type = 'positive';
                }
            } elseif (in_array($word, $this->negative_words)) {
                if ($negation) {
                    $word_score = (0.5 * $intensifier);
                    $positive_score += (0.5 * $intensifier);
                    $word_type = 'positive (negated negative)';
                } else {
                    $word_score = -(1.0 * $intensifier);
                    $negative_score += (1.0 * $intensifier);
                    $word_type = 'negative';
                }
            } else {
                $neutral_count++;
            }
            
            if ($word_type !== 'neutral') {
                $word_analysis[] = [
                    'word' => $word,
                    'type' => $word_type,
                    'score' => $word_score,
                    'intensifier' => $intensifier_word,
                    'negation' => $negation_word
                ];
            }
        }
        
        $this->analysis_steps['step2'] = [
            'title' => '2. Word Scoring',
            'description' => 'VADER checks each word in the feedback using its built-in dictionary, where every word already has an assigned emotion score (positive, negative, or neutral).',
            'words' => $word_analysis,
            'positive_score' => $positive_score,
            'negative_score' => $negative_score,
            'neutral_count' => $neutral_count
        ];
        
        // Step 4: Sentiment Calculation
        $total_sentiment = $positive_score - $negative_score;
        
        if ($total_sentiment == 0) {
            $compound = 0;
        } else {
            $compound = $total_sentiment / sqrt(($total_sentiment * $total_sentiment) + 15);
        }
        
        $this->analysis_steps['step4'] = [
            'title' => '4. Sentiment Calculation',
            'description' => 'All individual scores are added together to produce a total valence score, which is then normalized using VADER\'s compound formula.',
            'sum_of_valence' => $total_sentiment,
            'compound_raw' => $compound,
            'compound' => round($compound, 4),
            'formula' => [
                'numerator' => $total_sentiment,
                'denominator' => sqrt(($total_sentiment * $total_sentiment) + 15)
            ]
        ];
        
        // Calculate proportions
        $sum_scores = $positive_score + $negative_score + $neutral_count;
        if ($sum_scores > 0) {
            $pos = $positive_score / $sum_scores;
            $neg = $negative_score / $sum_scores;
            $neu = $neutral_count / $sum_scores;
        } else {
            $pos = 0;
            $neg = 0;
            $neu = 1;
        }
        
        // Step 5: Result Classification
        $sentiment = '';
        if ($compound >= 0.05) {
            $sentiment = 'Positive';
        } elseif ($compound <= -0.05) {
            $sentiment = 'Negative';
        } else {
            $sentiment = 'Neutral';
        }
        
        $this->analysis_steps['step5'] = [
            'title' => '5. Result Classification',
            'description' => 'The final sentiment is labeled as:',
            'rules' => [
                'positive' => 'Positive if the compound score is ≥ 0.05',
                'negative' => 'Negative if the compound score is ≤ -0.05',
                'neutral' => 'Neutral if the score is between -0.05 and 0.05'
            ],
            'result' => $sentiment,
            'compound' => round($compound, 4)
        ];
        
        return [
            'compound' => round($compound, 4),
            'pos' => round($pos, 3),
            'neg' => round($neg, 3),
            'neu' => round($neu, 3)
        ];
    }
    
    /**
     * Get HTML visualization of the analysis process (like the reference image)
     */
    public function getDetailedHtmlReport($text) {
        $scores = $this->polarity_scores($text);
        $sentiment = $this->analyze($text);
        $steps = $this->analysis_steps;
        
        $html = "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>";
        
        // Title
        $html .= "<h2 style='text-align: center; color: #333; margin-bottom: 30px; font-size: 28px;'>📊 Simplified Steps in VADER Sentiment Analysis</h2>";
        
        // Step 1: Text Cleaning
        if (isset($steps['step1'])) {
            $html .= $this->renderStep(
                '1',
                $steps['step1']['title'],
                $steps['step1']['description'],
                "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;'>
                    <strong>Original:</strong> <span style='color: #666;'>{$steps['step1']['original']}</span><br>
                    <strong>Cleaned:</strong> <span style='color: #0066cc;'>{$steps['step1']['cleaned']}</span>
                </div>"
            );
        }
        
        // Step 2: Word Scoring
        if (isset($steps['step2']) && !empty($steps['step2']['words'])) {
            $words_html = "<div style='margin-top: 10px;'>";
            $words_html .= "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
            $words_html .= "<tr style='background: #f1f3f5;'>
                <th style='padding: 10px; text-align: left; border: 1px solid #dee2e6;'>Word</th>
                <th style='padding: 10px; text-align: left; border: 1px solid #dee2e6;'>Type</th>
                <th style='padding: 10px; text-align: right; border: 1px solid #dee2e6;'>Score</th>
                <th style='padding: 10px; text-align: left; border: 1px solid #dee2e6;'>Context</th>
            </tr>";
            
            foreach ($steps['step2']['words'] as $w) {
                $color = strpos($w['type'], 'positive') !== false ? '#28a745' : '#dc3545';
                $context = [];
                if ($w['intensifier']) $context[] = "Intensified by '<strong>{$w['intensifier']}</strong>'";
                if ($w['negation']) $context[] = "Negated by '<strong>{$w['negation']}</strong>'";
                $context_text = !empty($context) ? implode(', ', $context) : '—';
                
                $words_html .= "<tr>
                    <td style='padding: 10px; border: 1px solid #dee2e6;'><strong>{$w['word']}</strong></td>
                    <td style='padding: 10px; border: 1px solid #dee2e6; color: {$color};'>{$w['type']}</td>
                    <td style='padding: 10px; border: 1px solid #dee2e6; text-align: right; font-weight: bold; color: {$color};'>{$w['score']}</td>
                    <td style='padding: 10px; border: 1px solid #dee2e6; font-size: 13px;'>{$context_text}</td>
                </tr>";
            }
            $words_html .= "</table>";
            
            $words_html .= "<div style='margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;'>
                <strong>Summary:</strong><br>
                Positive Score: <span style='color: #28a745; font-weight: bold;'>{$steps['step2']['positive_score']}</span> | 
                Negative Score: <span style='color: #dc3545; font-weight: bold;'>{$steps['step2']['negative_score']}</span> | 
                Neutral Words: <span style='color: #666; font-weight: bold;'>{$steps['step2']['neutral_count']}</span>
            </div>";
            $words_html .= "</div>";
            
            $html .= $this->renderStep(
                '2',
                $steps['step2']['title'],
                $steps['step2']['description'],
                $words_html
            );
        }
        
        // Step 3: Context Adjustment
        $html .= $this->renderStep(
            '3',
            'Context Adjustment',
            'The system adjusts scores depending on nearby words or punctuation (for example, <em>"not bad"</em> becomes slightly positive and <em>"very good!"</em> becomes stronger in positivity).',
            "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 10px;'>
                ℹ️ This adjustment is applied during word scoring (see Step 2 table above)
            </div>"
        );
        
        // Step 4: Sentiment Calculation
        if (isset($steps['step4'])) {
            $formula = $steps['step4']['formula'];
            $calculation_html = "
            <div style='margin-top: 15px; text-align: center;'>
                <div style='background: #f8f9fa; padding: 25px; border-radius: 12px; display: inline-block;'>
                    <div style='font-size: 18px; margin-bottom: 15px;'>
                        <strong>Compound Formula:</strong>
                    </div>
                    <div style='font-size: 24px; border: 2px solid #dee2e6; padding: 20px; border-radius: 8px; background: white;'>
                        Compound = 
                        <span style='font-size: 32px; vertical-align: middle;'>
                            <span style='display: inline-block; text-align: center;'>
                                <span style='display: block; border-bottom: 2px solid #333; padding-bottom: 5px;'>Sum of valence scores</span>
                                <span style='display: block; padding-top: 5px;'>√(Sum of valence scores)² + 15</span>
                            </span>
                        </span>
                    </div>
                    <div style='margin-top: 20px; font-size: 16px;'>
                        <strong>Calculation:</strong><br>
                        <div style='background: white; padding: 15px; border-radius: 8px; margin-top: 10px; border: 1px solid #dee2e6;'>
                            Compound = {$formula['numerator']} / {$formula['denominator']} = <strong style='color: #0066cc; font-size: 20px;'>{$steps['step4']['compound']}</strong>
                        </div>
                    </div>
                    <div style='margin-top: 15px; font-size: 14px; color: #666;'>
                        This formula converts the total sentiment value into a range between <strong>-1</strong> (most negative) and <strong>+1</strong> (most positive).
                    </div>
                </div>
            </div>";
            
            $html .= $this->renderStep(
                '4',
                $steps['step4']['title'],
                $steps['step4']['description'],
                $calculation_html
            );
        }
        
        // Step 5: Result Classification
        if (isset($steps['step5'])) {
            $result_color = $sentiment === 'Positive' ? '#28a745' : ($sentiment === 'Negative' ? '#dc3545' : '#ff9800');
            $result_emoji = $sentiment === 'Positive' ? '😊' : ($sentiment === 'Negative' ? '😞' : '😐');
            
            $classification_html = "
            <div style='margin-top: 15px;'>
                <ul style='list-style: none; padding: 0;'>
                    <li style='padding: 10px; margin: 8px 0; background: #e8f5e9; border-left: 4px solid #28a745; border-radius: 5px;'>
                        <strong>✓ Positive</strong> if the compound score is ≥ 0.05
                    </li>
                    <li style='padding: 10px; margin: 8px 0; background: #ffebee; border-left: 4px solid #dc3545; border-radius: 5px;'>
                        <strong>✗ Negative</strong> if the compound score is ≤ -0.05
                    </li>
                    <li style='padding: 10px; margin: 8px 0; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 5px;'>
                        <strong>⊙ Neutral</strong> if the score is between -0.05 and 0.05
                    </li>
                </ul>
                
                <div style='text-align: center; margin-top: 25px; padding: 30px; background: linear-gradient(135deg, {$result_color}22, {$result_color}11); border: 3px solid {$result_color}; border-radius: 15px;'>
                    <div style='font-size: 60px; margin-bottom: 15px;'>{$result_emoji}</div>
                    <div style='font-size: 32px; font-weight: bold; color: {$result_color}; margin-bottom: 10px;'>
                        {$steps['step5']['result']}
                    </div>
                    <div style='font-size: 18px; color: #666;'>
                        Compound Score: <strong style='color: {$result_color};'>{$steps['step5']['compound']}</strong>
                    </div>
                </div>
            </div>";
            
            $html .= $this->renderStep(
                '5',
                $steps['step5']['title'],
                $steps['step5']['description'],
                $classification_html
            );
        }
        
        $html .= "</div>";
        return $html;
    }
    
    /**
     * Helper function to render each step
     */
    private function renderStep($number, $title, $description, $content = '') {
        return "
        <div style='margin-bottom: 30px; padding: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; border-left: 5px solid #0066cc;'>
            <h3 style='color: #0066cc; margin: 0 0 15px 0; font-size: 22px;'>
                <span style='background: #0066cc; color: white; padding: 5px 15px; border-radius: 50px; margin-right: 10px; display: inline-block; min-width: 35px; text-align: center;'>{$number}</span>
                {$title}
            </h3>
            <p style='color: #555; line-height: 1.8; margin: 15px 0; font-size: 15px;'>{$description}</p>
            {$content}
        </div>";
    }
    
    /**
     * Clean word by removing punctuation
     */
    private function clean_word($word) {
        return preg_replace('/[^a-z]/', '', strtolower($word));
    }
    
    /**
     * Simple analysis (backward compatible)
     */
    public function getExpression($text) {
        $sentiment = $this->analyze($text);
        $scores = $this->polarity_scores($text);
        
        $expressions = [
            'Positive' => [
                'emoji' => '😊',
                'color' => '#4CAF50',
                'bg_color' => '#E8F5E9',
                'message' => 'Great feedback! Thank you!'
            ],
            'Negative' => [
                'emoji' => '😞',
                'color' => '#f44336',
                'bg_color' => '#FFEBEE',
                'message' => 'We apologize for your experience'
            ],
            'Neutral' => [
                'emoji' => '😐',
                'color' => '#FF9800',
                'bg_color' => '#FFF3E0',
                'message' => 'Thank you for your feedback'
            ]
        ];
        
        return [
            'sentiment' => $sentiment,
            'compound' => $scores['compound'],
            'expression' => $expressions[$sentiment],
            'scores' => $scores
        ];
    }
}

// Test usage
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $comment = implode(' ', array_slice($argv, 1));
    $analyzer = new SentimentAnalyzer();
    echo $analyzer->getDetailedHtmlReport($comment);
}
?>