# Intellibasket

This project takes a barcode number as a paramater (BCN) from endpoint, strips all data except numbers as a rudimentary code-injection attack prevention. it then gets a session key from the Tesco grocery API, looks up the barcode, and if it finds an exact match adds that item (using Tesco's SKU) to the users shopping basket. It will then send a push notification to the users device(s) using the Pushbullet API.

I have many more ideas for this project, additionally there is a lot of code that could be removed or simplified.