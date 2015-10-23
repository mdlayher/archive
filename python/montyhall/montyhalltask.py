# Monty Hall Paradox - CS5300 Final Project - Andy Ladd, Matt Layher

from pybrain.rl.environments.task import Task

# Overiddes Task Class from PyBrain.
class MontyHallTask(Task):
	# constructor
	def __init__(self, environment):
		self.environment = environment
		self.lastReward = 0

	# perform an action
	def performAction(self, action):
		self.action = self.environment.performAction(action)
		return self.action

	# get a reward
	def getReward(self, choice):
		# Check if winner
		if self.environment.isWinner(choice):
			self.reward = 1;
		else:
			self.reward = -1;

		self.lastReward = self.reward
		return self.reward
