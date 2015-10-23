import random

class Monty(object):
	def __init__(self, numIterations, switch):
		self.choices = ["0", "1", "2"]
		self.wins = 0
		self.losses = 0
		self.iterations = numIterations
		self.isSwitch = switch
		random.seed()

	def RunGame(self):
		for i in range(self.iterations):
			# randomize the choices
			random.shuffle(self.choices)

			# randomly pick a winner
			self.choiceWinner = random.choice(self.choices)

			# user picks a random choice
			self.choiceUser = random.choice(self.choices)

			# find a loser that's not the user's choice or the winning choice
			while (True):
				# grab a random item from choices and dub that as the temp loser
				tempLoser = random.choice(self.choices)

				# make sure the temp loser doesn't match the user's choice or the winning choice
				if (tempLoser != self.choiceUser and tempLoser != self.choiceWinner):
					# store the temp loser for later use and break from infinite loop
					self.choiceLoser = tempLoser
					break

			#self.isSwitch = random.choice([True, False])

			# if desired, user switches their choice
			if (self.isSwitch):
				# grab the only remaining choice, increasing your chances of winning
				while (True):
					# grab a random item from choices and dub that as the temp choice
					tempChoice = random.choice(self.choices)

					# make sure the temp choice doesn't match the user's choice or the revealed losing choice
					if (tempChoice != self.choiceUser and tempChoice != self.choiceLoser):
						# store the temp choice as the new user's choice and break from infinite loop
						self.choiceUser = tempChoice
						break

			# increment wins/losses
			if (self.choiceUser == self.choiceWinner):
				self.wins += 1
			else:
				self.losses += 1

# main method
def main():
	# print title
	print "Monty Hall Paradox - CS5300 Final Project - Andy Ladd, Matt Layher"
	print "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -"

	# set parameters
	numIterations = 100000
	switch = True

	# run game
	print "[main] running {0} iterations...".format(numIterations)
	game = Monty(numIterations, switch)
	game.RunGame()
	print "[main] iterations complete!"

	# print results
	print "[main] results:"
	print "\twins:  ", game.wins
	print "\tlosses:", game.losses

# run it!
main()
