# Monty Hall Paradox - CS5300 Final Project - Andy Ladd, Matt Layher

from pybrain.rl.environments.environment import Environment
import random

# Overiddes Environment Class from PyBrain.
# Allows us to reset the game and get the winning and loosing choices
class MontyHallEnv(Environment):
	def __init__(self):
		self.choices = ["0", "1", "2"]
		random.seed()
		self.reset()

	def reset(self):
		# shuffle the choices to create a better random effect
		random.shuffle(self.choices)

		# choosing a random winner makes it more realistic so that only "monty" knows the winner
		self.winningChoice = random.choice(self.choices)

		# have the user get a choice
		self.usersChoice = random.choice(self.choices)

	def getUsersChoice(self):
		return self.usersChoice

	def getWinner(self):
		return self.winningChoice

	def getLoser(self):
		return self.losingChoice

	def findLoser(self, usersChoice):
		# find a choice that's not the user's choice or the winning choice
		while (True):
			# grab a random item from choices and dub that as the currentChoice
			currentChoice = random.choice(self.choices)

			# make sure the currentChoice doesn't match the user's choice or the winning choice
			if (currentChoice != usersChoice and currentChoice != self.winningChoice):
				# return the current choice as the "loser"
				self.losingChoice = currentChoice
				return

	def isWinner(self, usersChoice):
		return (self.winningChoice == usersChoice)

	def performAction(self, action):
		# change users choice
		if (action == 0.):
			# grab the only remaining choice, increasing your chances of winning
			while (True):
				# grab a random item from choices and dub that as the temp choice
				tempChoice = random.choice(self.choices)

				# make sure the temp choice doesn't match the user's choice or the revealed losing choice
				if (tempChoice != self.usersChoice and tempChoice != self.losingChoice):
					# change their choice
					self.usersChoice = tempChoice
					break

			#return the action
			return action
