from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer
import sys

comment = " ".join(sys.argv[1:])
analyzer = SentimentIntensityAnalyzer()
score = analyzer.polarity_scores(comment)

if score['compound'] >= 0.05:
    print("Positive")
elif score['compound'] <= -0.05:
    print("Negative")
else:
    print("Neutral")
